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

class cc
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'CONSTRUCTION ORDER';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    public $expirystatus = ['readonly' => false, 'show' => true, 'showdate' => false];
    public $tablenum = 'transnum';
    public $head = 'cohead';
    public $hhead = 'hcohead';
    public $stock = 'costock';
    public $hstock = 'hcostock';
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';
    public $htablelogs = 'htransnum_log';
    private $stockselect;
    public $dqty = 'rrqty';
    public $hqty = 'qty';

    private $fields = ['trno', 'docno', 'dateid', 'due', 'client', 'clientname', 'address', 'yourref', 'ourref', 'rem', 'wh', 'projectid',  'phaseid', 'modelid', 'blklotid', 'lot', 'citrno'];
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
        $this->logger = new Logger;
        $this->reporter = new SBCPDF;
        $this->helpClass = new helpClass;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 4686,
            'edit' => 4687,
            'new' => 4688,
            'save' => 4689,
            'delete' => 4690,
            'print' => 4691,
            'lock' => 4692,
            'unlock' => 4693,
            'changeamt' => 4699,
            'post' => 4694,
            'unpost' => 4695,
            'additem' => 4696,
            'edititem' => 4697,
            'deleteitem' => 4698
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
        $doc = $config['params']['doc'];
        $center = $config['params']['center'];
        $condition = '';
        $searchfilter = $config['params']['search'];
        $limit = "limit 150";
        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['head.docno', 'head.clientname', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
            $limit = "";
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
     on num.trno=head.trno where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
     union all
     select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid,'POSTED' as status,head.createby,head.editby,head.viewby, num.postedby  
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
     order by dateid desc, docno desc " . $limit;

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
            'help'
        );
        $buttons = $this->btnClass->create($btns);
        $step1 = $this->helpClass->getFields(['btnnew', 'destination', 'dateid', 'cswhname', 'yourref', 'csrem', 'btnsave']);
        $step2 = $this->helpClass->getFields(['btnedit', 'destination', 'dateid', 'cswhname', 'yourref', 'csrem', 'btnsave']);
        $step3 = $this->helpClass->getFields(['btnadditem', 'btnquickadd', 'isqty', 'uom', 'isamt', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
        $step4 = $this->helpClass->getFields(['isqty', 'uom', 'isamt', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
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
        $isexpiry = $this->companysetup->getisexpiry($config['params']);
        $ispallet = 0;
        $action = 0;
        $rrqty = 1;
        $uom = 2;
        $project = 3;
        $phasename = 4;
        $housemodel = 5;
        $blk = 6;
        $lot = 7;
        $amenityname = 8;
        $subamenityname = 9;
        $wh = 10;
        $ref = 11;
        $rem = 12;
        $qa = 13;
        $void = 14;

        $column =  [
            'action', 'rrqty', 'uom', 'project', 'phasename', 'housemodel', 'blk', 'lot', 'amenityname',
            'subamenityname', 'wh', 'ref', 'rem', 'qa', 'void'
        ];
        $sortcolumn =  [
            'action', 'rrqty', 'uom', 'project', 'phasename', 'housemodel', 'blk', 'lot', 'amenityname',
            'subamenityname', 'wh', 'ref', 'rem', 'qa', 'void'
        ];

        $tab = [
            $this->gridname => [
                'gridcolumns' => $column, 'sortcolumns' => $sortcolumn,
                'headgridbtns' => []
            ]
        ];

        $stockbuttons = ['save', 'delete'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0]['inventory']['columns'][$project]['type'] = 'label';
        $obj[0]['inventory']['columns'][$phasename]['type'] = 'label';
        $obj[0]['inventory']['columns'][$housemodel]['type'] = 'label';
        $obj[0]['inventory']['columns'][$blk]['type'] = 'label';
        $obj[0]['inventory']['columns'][$lot]['type'] = 'label';

        $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['pendingcidetail', 'additem', 'quickadd', 'saveitem', 'deleteallitem'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function createHeadField($config)
    {
        $fields = ['docno', 'client', 'clientname', 'address'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.label', 'Customer Code');
        data_set($col1, 'clientname.label', 'Customer Name');
        data_set($col1, 'docno.label', 'Transaction#');

        $fields = ['dateid', 'due', 'dwhname', ['yourref', 'ourref']];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'whname.required', true);
        data_set($col2, 'whname.type', 'lookup');
        data_set($col2, 'whname.action', 'lookupclient');
        data_set($col2, 'whname.lookupclass', 'wh');

        $fields = ['dprojectname', 'phase', 'housemodel', ['blklot', 'lot']];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'projectname.lookupclass', 'fproject');
        data_set($col3, 'phase.addedparams', ['projectid']);
        data_set($col3, 'housemodel.addedparams', ['projectid']);
        data_set($col3, 'blklot.addedparams', ['projectid', 'phaseid', 'modelid', 'fpricesqm']);

        $fields = ['rem', 'cidocno'];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'cidocno.type', 'lookup');
        data_set($col4, 'cidocno.class', 'cscitrno sbccsreadonly');
        data_set($col4, 'cidocno.lookupclass', 'pendingci');
        data_set($col4, 'cidocno.action', 'pendingci');
        data_set($col4, 'cidocno.addedparams', ['modelid']);

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
        $data[0]['address'] = '';
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
        $data[0]['citrno'] = '';
        $data[0]['cidocno'] = '';

        return $data;
    }

    public function loadheaddata($config)
    {
        $doc = $config['params']['doc'];
        $trno = $config['params']['trno'];
        $center = $config['params']['center'];
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
        $qryselect = "select num.center,head.trno,head.docno,client.client,head.yourref,head.ourref,
                            left(head.dateid,10) as dateid,head.clientname,head.address,
                            date_format(head.createdate,'%Y-%m-%d') as createdate,head.rem,warehouse.client as wh,
                            warehouse.clientname as whname,'' as dwhname,left(head.due,10) as due,client.groupid,
                            head.projectid,ifnull(project.code,'') as projectcode,
                            ifnull(project.name,'') as projectname,'' as dprojectname,
                            phase.code as phase,head.phaseid,model.model as housemodel,head.modelid,bl.blk as blklot,head.blklotid,bl.lot,head.citrno,ci.docno as cidocno ";

        $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join projectmasterfile as project on project.line=head.projectid
        left join phase on phase.line=head.phaseid and phase.projectid=head.projectid
        left join housemodel as model on model.line=head.modelid and model.projectid=head.projectid
        left join blklot as bl on bl.line=head.blklotid and bl.phaseid=head.phaseid and bl.projectid=head.projectid
        left join hcihead as ci on ci.trno=head.citrno
        where head.trno = ? and num.doc=? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join projectmasterfile as project on project.line=head.projectid
        left join phase on phase.line=head.phaseid and phase.projectid=head.projectid
        left join housemodel as model on model.line=head.modelid and model.projectid=head.projectid
        left join blklot as bl on bl.line=head.blklotid and bl.phaseid=head.phaseid and bl.projectid=head.projectid
        left join hcihead as ci on ci.trno=head.citrno
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
            $stock = $this->openstock($head['trno'], $config);
            if (count($stock) == 0) {
                $this->autocreatestock($config, $data);
            } else {
                $citrno = $this->coreFunctions->getfieldvalue("cohead", "citrno", "trno=?", [$head['trno']]);
                $line = $this->coreFunctions->getfieldvalue("costock", "line", "refx=? and trno=?", [$citrno, $head['trno']]);
                if (!empty($line)) {
                    return ['status' => false, 'msg' => "Can`t update, delete first all items that are connected to Previous Construction Instruction"];
                } else {
                    $this->autocreatestock($config, $data);
                }
            }
            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
        } else {
            $data['doc'] = $config['params']['doc'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];

            $this->coreFunctions->sbcinsert($this->head, $data);
            $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
            $this->autocreatestock($config, $data);
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
        $user = $config['params']['user'];
        $qry = "select trno from " . $this->stock . " where trno=? and qty=0 limit 1";
        $isitemzeroqty = $this->coreFunctions->opentable($qry, [$trno]);
        if (!empty($isitemzeroqty)) {
            return ['status' => false, 'msg' => 'Posting failed. Check carefully, some items have zero quantity.'];
        }
        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        if ($this->othersClass->isposted($config)) {
            return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
        }
        //for hcohead
        $qry = "insert into " . $this->hhead . " (trno,doc,docno,client,clientname,address,dateid,due,wh,
                            yourref,ourref,projectid,phaseid,modelid,blklotid,lot,rem,citrno,lockuser,
                            lockdate,openby,users,createdate,createby,editby,editdate,viewby,viewdate,voiddate)
                select trno,doc,docno,client,clientname,address,dateid,due,wh,
                            yourref,ourref,projectid,phaseid,modelid,blklotid,lot,rem,citrno,lockuser,
                            lockdate,openby,users,createdate,createby,editby,editdate,viewby,viewdate,voiddate
                from " . $this->head . " where trno=? limit 1";
        $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

        if ($posthead) {
            $qry = " insert into " . $this->hstock . " (trno,line,barcode,itemname,uom,rem,rrqty,qty,qa,
                            void,refx,linex,ref,itemid,whid,amenity,subamenity,encodeddate,encodedby,
                            editby,editdate,projectid,phaseid,modelid,blklotid,lot)
                    select trno,line,barcode,itemname,uom,rem,rrqty,qty,qa,
                            void,refx,linex,ref,itemid,whid,amenity,subamenity,encodeddate,encodedby,
                            editby,editdate,projectid,phaseid,modelid,blklotid,lot
                    from " . $this->stock . " where trno =?";

            if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
                //update transnum
                $date = $this->othersClass->getCurrentTimeStamp();
                $data = ['postdate' => $date, 'postedby' => $config['params']['user'], 'statid' => 12];
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
        } else {
            return ['status' => false, 'msg' => 'Error on Posting Head'];
        }
    } //end function

    public function unposttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];
        $qry = "select trno from " . $this->hstock . " where trno=? and  (qa>0 or void<>0)";
        $data = $this->coreFunctions->opentable($qry, [$trno]);
        if (!empty($data)) {
            return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, either already served or have item voided...'];
        }
        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        $qry = "insert into " . $this->head . " (trno,doc,docno,client,clientname,address,dateid,due,wh,
                            yourref,ourref,projectid,phaseid,modelid,blklotid,lot,rem,citrno,lockuser,
                            lockdate,openby,users,createdate,createby,editby,editdate,viewby,viewdate,voiddate)
                select trno,doc,docno,client,clientname,address,dateid,due,wh,
                            yourref,ourref,projectid,phaseid,modelid,blklotid,lot,rem,citrno,lockuser,
                            lockdate,openby,users,createdate,createby,editby,editdate,viewby,viewdate,voiddate
                from " . $this->hhead . " where trno=? limit 1";
        //head
        if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {

            $qry = " insert into " . $this->stock . " (trno,line,barcode,itemname,uom,rem,rrqty,qty,qa,
                            void,refx,linex,ref,itemid,whid,amenity,subamenity,encodeddate,encodedby,
                            editby,editdate,projectid,phaseid,modelid,blklotid,lot)
                    select trno,line,barcode,itemname,uom,rem,rrqty,qty,qa,
                            void,refx,linex,ref,itemid,whid,amenity,subamenity,encodeddate,encodedby,
                            editby,editdate,projectid,phaseid,modelid,blklotid,lot
                    from " . $this->hstock . " where trno =?";
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

    private function getstockselect($config)
    {
        $sqlselect = "select stock.trno,stock.line,stock.barcode,stock.itemname,stock.uom,stock.rem,stock." . $this->hqty . " as qty,
                            FORMAT(stock." . $this->dqty . "," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as " . $this->dqty . ",
                            round((stock." . $this->hqty . "-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
                            case when stock.void=0 then 'false' else 'true' end as void,stock.refx,stock.linex,
                            stock.ref,stock.itemid,stock.whid,warehouse.client as wh,
                            warehouse.clientname as whname,stock.amenity,stock.subamenity,stock.encodeddate,stock.encodedby,
                            stock.editby,stock.editdate,stock.projectid,stock.phaseid,stock.modelid,stock.blklotid,stock.lot,
                            proj.code as project, proj.name as projectname,phase.code as phasename,model.model as housemodel,
                            ifnull(uom.factor,1) as uomfactor,am.description as amenityname,subam.description as subamenityname,
                            bl.blk,'' as bgcolor,case when stock.void=0 then '' else 'bg-red-2' end as errcolor";
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
            left join phase on phase.line=stock.phaseid and phase.projectid=stock.projectid
            left join housemodel as model on model.line=stock.modelid and model.projectid=stock.projectid
            left join blklot as bl on bl.line=stock.blklotid and bl.lot=stock.lot and bl.projectid=stock.projectid
            left join amenities as am on am.line= stock.amenity
            left join subamenities as subam on subam.line=stock.subamenity and subam.amenityid=stock.amenity
            where stock.trno =? 
            UNION ALL  
            " . $sqlselect . "  
            from $this->hstock as stock
            left join item on item.itemid=stock.itemid
            left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
            left join client as warehouse on warehouse.clientid=stock.whid
            left join projectmasterfile as proj on proj.line=stock.projectid
            left join phase on phase.line=stock.phaseid and phase.projectid=stock.projectid
            left join housemodel as model on model.line=stock.modelid and model.projectid=stock.projectid
            left join blklot as bl on bl.line=stock.blklotid and bl.lot=stock.lot and bl.projectid=stock.projectid
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
                from $this->stock as stock
                left join item on item.itemid=stock.itemid
                left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                left join client as warehouse on warehouse.clientid=stock.whid
                left join projectmasterfile as proj on proj.line=stock.projectid
                left join phase on phase.line=stock.phaseid and phase.projectid=stock.projectid
                left join housemodel as model on model.line=stock.modelid and model.projectid=stock.projectid
                left join blklot as bl on bl.line=stock.blklotid and bl.lot=stock.lot and bl.projectid=stock.projectid 
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
                return $this->additem('insert', $config);
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
            case 'getcidetails':
                return $this->getcidetails($config);
                break;
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
                break;
        }
    }

    private function autocreatestock($config, $data)
    {
        $qry = "select head.docno,stock.trno,stock.line,item.itemid,stock.barcode,stock.itemname,stock.uom,stock.rrqty,stock.qty
                from hcistock as stock
                left join item on item.barcode=stock.barcode
                left join hcihead as head on head.trno=stock.trno
                where stock.trno = " . $data['citrno'];

        $result = $this->coreFunctions->opentable($qry);
        foreach ($result as $key => $value) {
            $config['params']['data']['trno'] = $config['params']['trno'];

            $config['params']['data']['itemid'] = $result[$key]->itemid;
            $config['params']['data']['barcode'] = $result[$key]->barcode;
            $config['params']['data']['itemname'] = $result[$key]->itemname;
            $config['params']['data']['uom'] = $result[$key]->uom;
            $config['params']['data']['qty'] = $result[$key]->qty;
            $config['params']['data']['refx'] = $result[$key]->trno;
            $config['params']['data']['linex'] = $result[$key]->line;
            $config['params']['data']['ref'] = $result[$key]->docno;
            $config['params']['data']['wh'] = $data['wh'];
            $config['params']['data']['projectid'] = $data['projectid'];
            $config['params']['data']['phaseid'] = $data['phaseid'];
            $config['params']['data']['modelid'] = $data['modelid'];
            $config['params']['data']['blklotid'] = $data['blklotid'];
            $config['params']['data']['lot'] = $data['lot'];
            $this->additem('insert', $config);
        }
    }

    public function getcidetails($config)
    {
        $trno = $config['params']['trno'];
        $wh = $config['params']['wh'];
        $rows = [];

        $msg = '';

        foreach ($config['params']['rows'] as $key => $value) {

            $qry = "select head.docno,stock.trno,stock.line,item.itemid,stock.barcode,stock.itemname,
                           stock.uom,stock.rrqty,stock.qty
                    from hcistock as stock
                    left join item on item.barcode=stock.barcode
                    left join hcihead as head on head.trno=stock.trno
                    where stock.trno = ? and stock.line = ? and stock.qty>(stock.qa) and stock.void=0";

            $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);

            $projectid = $this->coreFunctions->getfieldvalue('cohead', 'projectid', 'trno=?', [$trno]);
            $phaseid = $this->coreFunctions->getfieldvalue('cohead', 'phaseid', 'trno=?', [$trno]);
            $modelid = $this->coreFunctions->getfieldvalue('cohead', 'modelid', 'trno=?', [$trno]);
            $blklotid = $this->coreFunctions->getfieldvalue('cohead', 'blklotid', 'trno=?', [$trno]);
            $lot = $this->coreFunctions->getfieldvalue('cohead', 'lot', 'trno=?', [$trno]);
            $wh = $this->coreFunctions->getfieldvalue('cohead', 'wh', 'trno=?', [$trno]);


            if (!empty($data)) {

                foreach ($data as $key2 => $value) {
                    $config['params']['data']['trno'] = $config['params']['trno'];

                    $config['params']['data']['itemid'] = $data[$key]->itemid;
                    $config['params']['data']['barcode'] = $data[$key]->barcode;
                    $config['params']['data']['itemname'] = $data[$key]->itemname;
                    $config['params']['data']['uom'] = $data[$key]->uom;
                    $config['params']['data']['qty'] = $data[$key]->qty;
                    $config['params']['data']['refx'] = $data[$key]->trno;
                    $config['params']['data']['linex'] = $data[$key]->line;
                    $config['params']['data']['ref'] = $data[$key]->docno;
                    $config['params']['data']['wh'] = $wh;
                    $config['params']['data']['projectid'] = $projectid;
                    $config['params']['data']['phaseid'] = $phaseid;
                    $config['params']['data']['modelid'] = $modelid;
                    $config['params']['data']['blklotid'] = $blklotid;
                    $config['params']['data']['lot'] = $lot;
                    $return = $this->additem('insert', $config);
                    if ($return['status']) {
                        if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line, $this->hqty) == 0) {
                            $line = $return['row'][0]->line;
                            $config['params']['trno'] = $trno;
                            $config['params']['line'] = $line;
                            $this->setserveditems($data[$key2]->trno, $data[$key2]->line, $this->hqty);
                            $row = $this->openstockline($config);
                            $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
                        }
                        array_push($rows, $return['row'][0]);
                    } else {
                        $msg .= "  " . $return['msg'];
                    }
                } // end foreach
            } //end if
        } //end foreach

        if ($msg == '') {
            $msg = 'Items were successfully added.';
        }

        return ['row' => $rows, 'status' => true, 'msg' => $msg, 'reloadhead' => true, 'trno' => $trno];
    } //end function



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
                if ($data[$key]->refx == 0) {
                    $msg1 = ' Out of stock ';
                } else {
                    $msg2 = ' Qty Received is Greater than Request Qty ';
                }
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
                if ($data[$key]->refx == 0) {
                    $msg1 = ' Out of stock ';
                } else {
                    $msg2 = ' Qty Received is Greater than Request Qty ';
                }
            }
        }
        if ($isupdate) {
            return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
        } else {
            return ['inventory' => $data, 'status' => true, 'msg' => 'Please check, some items have zero qty (' . $msg1 . ' / ' . $msg2 . ')'];
        }
    } //end function


    public function addallitem($config)
    {
        $msg = '';
        foreach ($config['params']['row'] as $key => $value) {
            $config['params']['data'] = $value;
            $res = $this->additem('insert', $config);
            if ($res['status']) {
                if ($res['msg'] != '') {
                    $msg .= $res['msg'] . " " . $config['params']['data']['itemname'];
                }
            }
        }
        if ($msg == '') {
            $msg = 'Successfully saved.';
        }
        $data = $this->openstock($config['params']['trno'], $config);
        return ['inventory' => $data, 'status' => true, 'msg' => $msg];
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
        $item = $this->coreFunctions->opentable("select item.itemid,item.amt,item.disc,'' as loc,'" . $wh . "' as wh, 1 as qty, uom,'' as expiry,'' as rem from item where barcode=?", [$barcode]);
        $item = json_decode(json_encode($item), true);

        if (!empty($item)) {
            $config['params']['barcode'] = $barcode;
            $lprice = $this->getlatestprice($config);
            $lprice = json_decode(json_encode($lprice), true);
            if (!empty($lprice['data'])) {
                $item[0]['amt'] = $lprice['data'][0]['amt'];
                $item[0]['disc'] = $lprice['data'][0]['disc'];
            }

            $config['params']['data'] = $item[0];
            return $this->additem('insert', $config);
        } else {
            return ['status' => false, 'msg' => 'Barcode not found.', ''];
        }
    }

    // insert and update item
    public function additem($action, $config)
    {
        $trno = $config['params']['trno'];
        $itemid = $config['params']['data']['itemid'];
        $barcode = $config['params']['data']['barcode'];
        $itemname = $config['params']['data']['itemname'];
        $uom = $config['params']['data']['uom'];
        $wh = $config['params']['data']['wh'];
        $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);

        $rem = '';
        if (isset($config['params']['data']['rem'])) {
            $rem = $config['params']['data']['rem'];
        }

        $refx = 0;
        $linex = 0;
        $ref = '';
        $projectid = 0;
        $phaseid = 0;
        $modelid = 0;
        $blklotid = 0;
        $lot = '';
        $amenity = 0;
        $subamenity = 0;
        $void = 'false';
        if (isset($config['params']['data']['refx'])) {
            $refx = $config['params']['data']['refx'];
        }
        if (isset($config['params']['data']['linex'])) {
            $linex = $config['params']['data']['linex'];
        }
        if (isset($config['params']['data']['ref'])) {
            $ref = $config['params']['data']['ref'];
        }

        if (isset($config['params']['data']['projectid'])) {
            $projectid = $config['params']['data']['projectid'];
        } else {
            $projectid = $this->coreFunctions->getfieldvalue("cohead", "projectid", "trno=?", [$trno]);
        }

        if (isset($config['params']['data']['phaseid'])) {
            $phaseid = $config['params']['data']['phaseid'];
        } else {
            $phaseid = $this->coreFunctions->getfieldvalue("cohead", "phaseid", "trno=?", [$trno]);
        }

        if (isset($config['params']['data']['modelid'])) {
            $modelid = $config['params']['data']['modelid'];
        } else {
            $modelid = $this->coreFunctions->getfieldvalue("cohead", "modelid", "trno=?", [$trno]);
        }
        if (isset($config['params']['data']['blklotid'])) {
            $blklotid = $config['params']['data']['blklotid'];
        } else {
            $blklotid = $this->coreFunctions->getfieldvalue("cohead", "blklotid", "trno=?", [$trno]);
        }
        if (isset($config['params']['data']['lot'])) {
            $lot = $config['params']['data']['lot'];
        } else {
            $lot = $this->coreFunctions->getfieldvalue("cohead", "lot", "trno=?", [$trno]);
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
            if ($item[0]->factor !== 0) $factor = $item[0]->factor;
        }

        $data = [
            'trno' => $trno,
            'line' => $line,
            'itemid' => $itemid,
            'barcode' => $barcode,
            'itemname' => $itemname,
            $this->dqty => $qty,
            $this->hqty => $qty,
            'refx' => $refx,
            'linex' => $linex,
            'ref' => $ref,
            'uom' => $uom,
            'rem' => $rem,
            'whid' => $whid,
            'projectid' => $projectid,
            'phaseid' => $phaseid,
            'modelid' => $modelid,
            'blklotid' => $blklotid,
            'lot' => $lot,
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

        if ($action == 'insert') {
            $data['encodeddate'] = $current_timestamp;
            $data['encodedby'] = $config['params']['user'];

            if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
                $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode);
                $havestock = true;

                $row = $this->openstockline($config);
                $msg = 'Item was successfully added.';

                return ['row' => $row, 'status' => true, 'msg' => $msg];
            } else {
                return ['status' => false, 'msg' => 'Add item Failed'];
            }
        } elseif ($action == 'update') {
            $return = true;
            $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);

            if ($refx != 0) {
                if ($this->setserveditems($refx, $linex) === 0) {
                    $this->setserveditems($refx, $linex);
                    $return = false;
                }
            }

            return $return;
        }
    } // end function

    public function getlatestprice($config)
    {
        $barcode = $config['params']['barcode'];
        $center = $config['params']['center'];
        $trno = $config['params']['trno'];
        $forex = $this->coreFunctions->getfieldvalue($this->head, "forex", "trno=?", [$trno]);

        $qry = "select docno,left(dateid,10) as dateid,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,disc,uom from(select head.docno,head.dateid,
        stock.rrcost as amt,stock.uom,stock.disc
        from lahead as head
        left join lastock as stock on stock.trno = head.trno
        left join cntnum on cntnum.trno=head.trno
        left join item on item.itemid=stock.itemid
        where head.doc = 'RR' and cntnum.center = ?
        and item.barcode = ? and head.client = ?
        and stock.rrcost <> 0
        UNION ALL
        select head.docno,head.dateid,stock.rrcost as computeramt,
        stock.uom,stock.disc from glhead as head
        left join glstock as stock on stock.trno = head.trno
        left join item on item.itemid = stock.itemid
        left join client on client.clientid = head.clientid
        left join cntnum on cntnum.trno=head.trno
        where head.doc = 'RR' and cntnum.center = ?
        and item.barcode = ? and client.client = ?
        and stock.rrcost <> 0
        order by dateid desc limit 5) as tbl order by dateid desc limit 1";
        $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $center, $barcode]);
        if (!empty($data)) {
            return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
        } else {
            return ['status' => false, 'msg' => 'No Latest price found...'];
        }
    } //end function

    public function deleteallitem($config)
    {
        $trno = $config['params']['trno'];
        if ($this->companysetup->getserial($config['params'])) {
            $data2 = $this->coreFunctions->opentable('select trno,line from ' . $this->stock . ' where trno=?', [$trno]);
            foreach ($data2 as $key => $value) {
                $this->othersClass->deleteserialout($data2[$key]->trno, $data2[$key]->line);
            }
        }

        $data = $this->coreFunctions->opentable('select refx,linex from ' . $this->stock . ' where trno=? and refx<>0', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from costing where trno=?', 'delete', [$trno]);
        foreach ($data as $key => $value) {
            $this->setserveditems($data[$key]->refx, $data[$key]->linex);
        }
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
        return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
    }

    public function setserveditems($refx, $linex)
    {
        $qry1 = "select stock." . $this->hqty . " from cohead as head left join costock as 
                    stock on stock.trno=head.trno where head.doc='CC' and stock.refx=" . $refx . " and stock.linex=" . $linex;

        $qry1 = $qry1 . " union all select hcostock." . $this->hqty . " from hcohead left join hcostock on hcostock.trno=
                    hcohead.trno where hcohead.doc='CC' and hcostock.refx=" . $refx . " and hcostock.linex=" . $linex;

        $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
        $qty = $this->coreFunctions->datareader($qry2);
        if ($qty === '') {
            $qty = 0;
        }
        return $this->coreFunctions->execqry("update hcistock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
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

        $qry = "delete from " . $this->stock . " where trno=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
        $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
        if ($data[0]->refx !== 0) {
            $this->setserveditems($data[0]->refx, $data[0]->linex);
        }
        $data = json_decode(json_encode($data), true);
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' barcode:' . $data[0]['barcode'] . ' Qty:' . $data[0][$this->dqty]);
        return ['status' => true, 'msg' => 'Item was successfully deleted.'];
    } // end function


    public function reportsetup($config)
    {
        $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
        $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);
        $modulename = $this->modulename;
        $data = [];
        $style = 'width:500px;max-width:500px;';
        return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false, 'reloadhead' => true];
    }

    public function reportdata($config)
    {
        $companyid = $config['params']['companyid'];
        $dataparams = $config['params']['dataparams'];
        $this->logger->sbcviewreportlog($config);
        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'reloadhead' => true];
    }
} //end class
