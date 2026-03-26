<?php

namespace App\Http\Classes\modules\unitechindustry;

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

class mi
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Raw Material Issuance';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $sqlquery;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showstage' => true];
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
    public $dqty = 'isqty'; //1
    public $hqty = 'iss';   //1
    public $damt = 'isamt'; //85
    public $hamt = 'amt'; //85
    public $defaultContra = 'WIP';
    private $stockselect;
    private $fields = [
        'trno',
        'docno',
        'dateid',
        'yourref',
        'ourref',
        'rem',
        'contra',
        'client',
        'clientname',
        'wh',
        'petrno'
    ];
    private $otherfields = [];
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
            'view' => 769,
            'edit' => 770,
            'new' => 771,
            'save' => 772,
            // 'change' => 773, remove change doc
            'delete' => 774,
            'print' => 775,
            'lock' => 776,
            'unlock' => 777,
            'acctg' => 783,
            'post' => 778,
            'unpost' => 779,
            'additem' => 2057,
            'edititem' => 2058,
            'deleteitem' => 2059
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $getcols = [
            'action', 'liststatus', 'listdocument', 'listdate', 'barcode', 'itemdesc',
            'yourref', 'ourref', 'rem', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'
        ];

        foreach ($getcols as $key => $value) {
            $$value = $key;
        }
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$listdocument]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;';
        $cols[$barcode]['style'] = 'width:110px;whiteSpace: normal;min-width:110px;';
        $cols[$itemdesc]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
        $cols[$listdate]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;';
        $cols[$yourref]['align'] = 'text-left';
        $cols[$ourref]['align'] = 'text-left';
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
            $searchfield = ['head.docno', 'i.barcode', 'i.itemname', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
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
        $qry = "select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid, 
                      'DRAFT' as status,head.createby,head.editby,head.viewby,num.postedby,
                      head.yourref,head.ourref,head.rem,i.barcode, i.itemname as itemdesc
                from " . $this->head . " as head 
                left join " . $this->tablenum . " as num on num.trno=head.trno 
                left join hprhead as pe on pe.trno=head.petrno
                left join item as i on i.itemid = pe.itemid
                where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? 
                        and CONVERT(head.dateid,DATE)<=? " . $condition . $projectfilter . " " . $filtersearch . "
                union all
                select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid,
                       'POSTED' as status,head.createby,head.editby,head.viewby, num.postedby,
                       head.yourref,head.ourref,head.rem,i.barcode, i.itemname as itemdesc
                from " . $this->hhead . " as head 
                left join " . $this->tablenum . " as num on num.trno=head.trno 
                left join hprhead as pe on pe.trno=head.petrno
                left join item as i on i.itemid = pe.itemid
                where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? 
                      and CONVERT(head.dateid,DATE)<=? " . $condition . $projectfilter . " " . $filtersearch . "
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

        $buttons['others']['items'] = [
            'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
            'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
            'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
            'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
        ];

        if ($this->companysetup->getisshowmanual($config['params'])) {
            $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'mi', 'title' => 'Material Issuance Manual', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
        }
        return $buttons;
    } // createHeadbutton

    public function createTab($access, $config)
    {
        $column = ['action', 'isqty', 'uom', 'loc', 'isamt', 'ext', 'itemname'];
        foreach ($column as $key => $value) {
            $$value = $key;
        }

        $headgridbtns = ['viewdistribution', 'viewref', 'viewdiagram'];
        $tab = [
            $this->gridname => [
                'gridcolumns' => $column,
                'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'],
                'headgridbtns' => $headgridbtns
            ]
        ];

        $stockbuttons = ['save', 'delete', 'showbalance'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0]['inventory']['columns'][$isamt]['readonly'] = true;
        $obj[0]['inventory']['columns'][$loc]['label'] = 'Brand';
        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);

        return $obj;
    }

    public function createtab2($access, $config)
    {
        $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrycntnumpicture', 'label' => 'Attachment', 'access' => 'view']];
        $obj = $this->tabClass->createtab($tab, []);

        $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
        return $return;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['addcor', 'saveitem', 'deleteallitem'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[0]['label'] = 'PR';
        return $obj;
    }

    public function createHeadField($config)
    {
        $fields = ['docno', 'pedocno', 'barcode', 'itemname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'docno.label', 'Transaction#');
        data_set($col1, 'barcode.type', 'input');
        data_set($col1, 'barcode.class', 'csbarcode sbccsreadonly');
        data_set($col1, 'itemname.class', 'csitemname sbccsreadonly');
        data_set($col1, 'itemname.readonly', true);

        $fields = ['dateid', 'rem', 'dwhname', 'dacnoname'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'dwhname.required', true);

        data_set($col2, 'dacnoname.label', 'Account');
        data_set($col2, 'dacnoname.lookupclass', '');
        data_set($col2, 'dacnoname.required', true);

        return array('col1' => $col1, 'col2' => $col2);
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
        $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
        $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);
        $data[0]['client'] = '';
        $data[0]['clientname'] = '';
        $data[0]['dwhname'] = '';
        $data[0]['wh'] = $this->companysetup->getwh($params);
        $name = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['wh']]);
        $data[0]['whname'] = $name;
        $data[0]['petrno'] = 0;
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
                    head.trno, 
                    head.docno,
                    left(head.dateid,10) as dateid, 
                    head.yourref,
                    head.ourref,
                    head.rem,
                    head.contra,
                    coa.acnoname,
                    '' as dacnoname,
                    client.client,
                    head.clientname,
                    warehouse.client as wh,
                    warehouse.clientname as whname, 
                    '' as dwhname,
                    date_format(head.createdate,'%Y-%m-%d') as createdate,
                    head.createby,
                    date_format(head.editdate,'%Y-%m-%d') as editdate,
                    head.editby,
                    date_format(head.viewdate,'%Y-%m-%d') as viewdate,
                    head.viewby,head.petrno,i.barcode,i.itemname,pe.docno as pedocno";

        $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join coa on coa.acno=head.contra 
        left join hprhead as pe on pe.trno=head.petrno
        left join item as i on i.itemid = pe.itemid
      
        where head.trno = ? and num.doc=? and num.center = ? " . $projectfilter . "
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.clientid = client.clientid
        left join client as warehouse on warehouse.clientid = head.whid
        left join coa on coa.acno=head.contra 
        left join hprhead as pe on pe.trno=head.petrno
        left join item as i on i.itemid = pe.itemid
    
        where head.trno = ? and num.doc=? and num.center=? " . $projectfilter;

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

            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
            $this->autoplot_pe($head['trno'], $data, $config);
        } else {
            $data['doc'] = $config['params']['doc'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $this->coreFunctions->sbcinsert($this->head, $data);
            $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
            $this->autoplot_pe($head['trno'], $data, $config);
        }
    } // end function

    private function autoplot_pe($trno, $data, $config)
    {
        $rows = [];
        $msg = '';
        $qry = "select petrno as value from lahead where trno=" . $data["trno"];
        $petrno = $this->coreFunctions->datareader($qry);
        $wh = $this->coreFunctions->getfieldvalue($this->head, "wh", "trno=?", [$data["trno"]]);

        if ($petrno != 0) {

            $this->coreFunctions->execqry('delete from lastock where trno=?', 'delete', [$trno]);
            $trno = $data["trno"];
            $stockdata =  $this->coreFunctions->opentable("select " . $data["trno"] . " as trno,line,itemid,whid,uom,rrcost,ext,rem,ref,qty as pending,rrqty from hprstock where trno =" . $data["petrno"]);

            foreach ($stockdata as $key => $data) {
                $config['params']['data']['uom'] = $data->uom;
                $config['params']['data']['rem'] = $data->rem;
                $config['params']['data']['qty'] = $data->pending;
                $config['params']['data']['rrqty'] = $data->rrqty;
                $config['params']['data']['ref'] = $data->ref;
                $config['params']['data']['refx'] = $petrno;
                $config['params']['data']['linex'] = $data->line;
                $config['params']['data']['itemid'] = $data->itemid;
                $config['params']['data']['wh'] = $wh;
                $config['params']['data']['ext'] = $data->ext;
                $dateid = $this->coreFunctions->getfieldvalue($this->head, 'dateid', 'trno=?', [$trno]);
                $wh = $this->coreFunctions->getfieldvalue($this->head, 'wh', 'trno=?', [$trno]);
                $config['params']['data']['amt'] = $this->othersClass->getlatestcost($data->itemid, $dateid, $config, $wh);
                $return =  $this->additem('insert', $config);

                if ($msg = '') {
                    $msg = $return['msg'];
                } else {
                    $msg = $msg . $return['msg'];
                }

                if ($return['status']) {
                    if ($this->setserveditems($data->trno, $data->line) == 0) {
                        $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                        $line = $return['row'][0]->line;
                        $config['params']['trno'] = $trno;
                        $config['params']['line'] = $line;
                        $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                        $this->setserveditems($data->trno, $data->line);
                        $row = $this->openstockline($config);
                        $return = ['row' => $row, 'status' => true, 'msg' => $msg];
                    }
                    array_push($rows, $return['row'][0]);
                }
            }


            return [
                'row' => $rows,
                'status' => true,
                'msg' => $msg
            ];
        }
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
        $this->coreFunctions->execqry('delete from stockinfo where trno=?', 'delete', [$trno]);
        $this->othersClass->deleteattachments($config);
        $this->logger->sbcdel_log($trno, $config, $docno);
        return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
    } //end function

    public function posttrans($config)
    {
        $trno = $config['params']['trno'];
        $systemtype = $this->companysetup->getsystemtype($config['params']);

        if ($this->companysetup->isinvonly($config['params'])) {
            return $this->othersClass->posttranstock($config);
        } else {
            $checkacct = $this->othersClass->checkcoaacct(['IN1']);

            if ($checkacct != '') {
                return ['trno' => $trno, 'status' => false, 'msg' => 'Accounts not yet setup:' . $checkacct];
            }


            if ($this->othersClass->postcntnuminfo($config, true)) {
                $this->coreFunctions->execqry('delete from cntnuminfo where trno=?', 'delete', [$trno]);
            }

            $stock = $this->openstock($trno, $config);
            $checkcosting = $this->othersClass->checkcosting($stock);
            if ($checkcosting != '') {
                return ['trno' => $trno, 'status' => false, 'msg' => 'Unable to Post. ' . $checkcosting];
            }

            if (!$this->createdistribution($config)) {
                return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Problems in creating accounting entries.'];
            } else {
                return $this->othersClass->posttranstock($config);
            }
        }
    } //end function

    public function unposttrans($config)
    {
        return $this->othersClass->unposttranstock($config);
    } //end function


    private function getstockselect($config)
    {
        $sqlselect = "select item.brand as brand,
      ifnull(mm.model_name,'') as model,
      item.itemid,
      stock.trno, 
      stock.line,
      stock.refx, 
      stock.linex,
      item.barcode, 
      item.itemname,
      stock.uom, 
      stock.rrcost,
      stock.cost,
      stock." . $this->hamt . ", 
      stock." . $this->hqty . " as iss,
      FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt,
      FORMAT(stock." . $this->dqty . "," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as isqty,
      stock." . $this->hqty . " as rrqty,
      FORMAT(stock." . $this->dqty . "," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as qty,
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
      ifnull(uom.factor,1) as uomfactor,
      '' as bgcolor,
      '' as errcolor";
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
      where stock.trno =? 
      UNION ALL  
      " . $sqlselect . "  
      FROM $this->hstock as stock 
      left join $this->hhead as head on head.trno = stock.trno
      left join item on item.itemid=stock.itemid 
      left join model_masterfile as mm on mm.model_id = item.model
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom  
      left join client as warehouse on warehouse.clientid=stock.whid
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
            case 'getpendingpe':
                return $this->getpendingpe($config);
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
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
                break;
        }
    }

    public function getpendingpe($config)
    {
        $trno = $config['params']['trno'];
        $rows = [];
        $msg = '';

        foreach ($config['params']['rows'] as $key => $data) {
            $config['params']['data']['uom'] = $data['uom'];
            $config['params']['data']['rem'] = $data['rem'];
            $config['params']['data']['qty'] = $data['pending'];
            $config['params']['data']['ref'] = $data['ref'];
            $config['params']['data']['refx'] = $data['trno'];
            $config['params']['data']['linex'] = $data['line'];
            $config['params']['data']['itemid'] = $data['itemid'];
            $config['params']['data']['whid'] = $data['whid'];
            $config['params']['data']['ext'] = $data['ext'];
            $config['params']['data']['amt'] =  $data['rrcost'];

            $return =  $this->additem('insert', $config);

            if ($msg = '') {
                $msg = $return['msg'];
            } else {
                $msg = $msg . $return['msg'];
            }

            if ($return['status']) {
                if ($this->setserveditems($data['trno'], $data['line']) == 0) {
                    $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                    $line = $return['row'][0]->line;
                    $config['params']['trno'] = $trno;
                    $config['params']['line'] = $line;
                    $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                    $this->setserveditems($data['trno'], $data['line']);
                    $row = $this->openstockline($config);
                    $return = ['row' => $row, 'status' => true, 'msg' => $msg];
                }
                array_push($rows, $return['row'][0]);
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
                    $msg = '';
                    $maxqty = $this->get_max_qty($data2[$key]['refx'], $data2[$key]['linex']);

                    if ($config['params']['row']['iss'] > $maxqty) {
                        $msg = "QTY can not be greater than Max QTY";
                    } else {
                        $msg2 = ' Qty Received is Greater than PR Qty. ';
                        $msg = 'Pls check some items have zero qty (' . $msg1 . ' / ' . $msg2 . ')';
                    }
                }
            }
        }

        if (!$isupdate) {
            return ['row' => $data, 'status' => true, 'msg' => $msg];
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
                    $msg = '';
                    $maxqty = $this->get_max_qty($data2[$key]['refx'], $data2[$key]['linex']);
                    if ($data2[$key][$this->dqty] > $maxqty) {
                        $msg = "QTY can not be greater than Max QTY";
                    }
                }
            }
        }
        if ($isupdate) {
            return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
        } else {
            return ['inventory' => $data, 'status' => true, 'msg' => $msg];
        }
    } //end function

    public function get_max_qty($refx, $linex)
    {
        $maxqty = $this->coreFunctions->datareader("select ifnull(maxqty,0) as value from hprstock where trno=? and line=?", [$refx, $linex]);
        return $maxqty;
    }

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

            if (isset($config['params']['data']['refx'])) {
                if ($config['params']['data']['refx'] != 0) {
                    if ($this->setserveditems($config['params']['data']['refx'], $config['params']['data']['linex']) == 0) {
                        $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                        $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $row['row'][0]->trno, 'line' => $row['row'][0]->line]);
                        $this->setserveditems($config['params']['data']['refx'], $config['params']['data']['linex']);
                        if ($msg != '') {
                            $msg = $msg . '(' . $row['row'][0]->barcode . ') Issued Qty is Greater than SO Qty ';
                        } else {
                            $msg = '(' . $row['row'][0]->barcode . ') Issued Qty is Greater than SO Qty ';
                        }
                    }
                }
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
        $item = $this->coreFunctions->opentable("select item.itemid,item.amt,item.disc,'' as loc,'" . $wh . "' as wh, 1 as qty, uom, '' as expiry from item where barcode=?", [$barcode]);
        if (!empty($item)) {
            $config['params']['barcode'] = $barcode;
            $data = $this->getlatestprice($config);

            if (!empty($data)) {
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
    public function additem($action, $config, $setlog = false)
    {
        $companyid = $config['params']['companyid'];
        $trno = $config['params']['trno'];

        $expiry = '';
        $uom = isset($config['params']['data']['uom']) ? $config['params']['data']['uom'] : '';
        $isqty = isset($config['params']['data']['qty']) ? $config['params']['data']['qty'] : 0;
        $itemid = isset($config['params']['data']['itemid']) ? $config['params']['data']['itemid'] : 0;

        $refx =   isset($config['params']['data']['refx']) ? $config['params']['data']['refx'] : 0;
        $linex = isset($config['params']['data']['linex']) ? $config['params']['data']['linex'] : 0;
        $ref = isset($config['params']['data']['ref']) ? $config['params']['data']['ref'] : 0;
        $wh = isset($config['params']['data']['wh']) ? $config['params']['data']['wh'] : '';


        $disc = '';
        $loc = '';

        if (isset($config['params']['data']['loc'])) {
            $loc = $config['params']['data']['loc'];
        }

        $factor = 1;
        $amt = 0;
        $isnoninv = 0;
        $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor,item.isnoninv from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
        $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
        if (!empty($item)) {
            $isnoninv = $item[0]->isnoninv;
            $item[0]->factor = $this->othersClass->val($item[0]->factor);
            if ($item[0]->factor !== 0) $factor = $item[0]->factor;
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
            $amt = $config['params']['data']['amt'];
        } elseif ($action == 'update') {
            $config['params']['line'] = $config['params']['data']['line'];
            $line = $config['params']['data']['line'];
            $config['params']['line'] = $line;
            $amt = $config['params']['data'][$this->damt];
            $isqty = $config['params']['data'][$this->dqty];
        }

        $amt = $this->othersClass->sanitizekeyfield('amt', $amt);
        $isqty = $this->othersClass->sanitizekeyfield('isqty', $isqty);

        $cur = $this->coreFunctions->getfieldvalue($this->head, 'cur', 'trno=?', [$trno]);
        $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
        $computedata = $this->othersClass->computestock($amt, $disc, $isqty, $factor, 0, $cur);
        if (floatval($forex) == 0) {
            $forex = 1;
        }

        $wh = $this->coreFunctions->getfieldvalue('lahead', 'wh', 'trno=?', [$trno]);
        $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);

        if ($loc == '') {
            $qry = "select distinct loc from rrstatus where itemid =? and whid =?";
            $stockloc = $this->coreFunctions->opentable($qry, [$itemid, $whid]);
            if (count($stockloc) == 1) {
                $loc = $stockloc[0]->loc;
            }
        }

        $data = [
            'trno' => $trno,
            'line' => $line,
            'itemid' => $itemid,
            'isqty' => $isqty,
            'iss' => $computedata['qty'],
            'isamt' => $amt,
            'amt' => round($computedata['amt'] * $forex, 2),
            'ext' => $computedata['ext'],
            'whid' => $whid,
            'loc' => $loc,
            'uom' => $uom,
            'refx' => $refx,
            'linex' => $linex,
            'expiry' => $expiry
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
            $status_insert = $this->coreFunctions->sbcinsert($this->stock, $data);
            if ($status_insert == 1) {
                $havestock = true;
                $msg = 'Item was successfully added.';
                $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $isqty . ' Amt:' . $amt . ' wh:' . $wh . ' ext:' . $computedata['ext'] . ' Uom:' . $uom);

                if ($isnoninv == 0) {

                    $cost = $this->othersClass->computecosting($data['itemid'], $data['whid'], $data['loc'], $expiry, $trno, $line, $data['iss'], $config['params']['doc'], $config['params']['companyid']);
                    if ($cost != -1) {
                        $cost2 = $cost / $factor;
                        $computedata = $this->othersClass->computestock($cost2, $disc, $isqty, $factor);
                        $this->coreFunctions->sbcupdate($this->stock, ['cost' => $cost, 'isamt' => $cost2, 'amt' => $computedata['amt'], 'ext' => $computedata['ext']], ['trno' => $trno, 'line' => $line]); //amt is also the cost

                        //CHECK BELOW COST
                        if ($this->companysetup->checkbelowcost($config['params'])) {
                            $belowcost = $this->othersClass->checkbelowcost($trno, $line, $config);
                            if ($belowcost == 1) {
                                $msg = '(' . $item[0]->barcode . ') Is this free of charge? Please check.';
                            } elseif ($belowcost == 2) {
                                $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'BELOW COST', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
                                $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
                                $this->logger->sbcwritelog($trno, $config, 'STOCK', 'BELOW COST - Line:' . $line . ' Barcode:' . $item[0]->barcode . ' Qty' . $isqty . ' Amt:' . $data['amt'] . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0', $setlog ? $this->tablelogs : '');
                                $msg = "(" . $item[0]->barcode . ") You can't issue this item/s because it's BELOW COST!!!";
                            }
                        }
                    } else {
                        $havestock = false;
                        $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
                        $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
                        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $line . ' Barcode:' . $item[0]->barcode . ' Qty' . $isqty . ' Amt:' . $data['amt'] . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0', $setlog ? $this->tablelogs : '');
                        $msg = 'OUT OF STOCK.1';
                    }

                    if ($this->setserveditems($refx, $linex) == 0) {
                        $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                        $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                        $this->setserveditems($refx, $linex);
                        $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
                        $return = false;
                        $msg = "(" . $item[0]->barcode . ") Qty Received is Greater than Qty. (insert)";
                    }
                }

                $row = $this->openstockline($config);
                if (!$havestock) {
                    $row[0]->errcolor = 'bg-red-2';
                    $msg = '(' . $item[0]->barcode . ') Out of Stock.2';
                }
                return ['row' => $row, 'status' => true, 'msg' => $msg];
            } else {
                return ['status' => false, 'msg' => 'Add item Failed'];
            }
        } elseif ($action == 'update') {
            $return = true;
            $msg = '';
            $status_update = $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
            if ($status_update == 1) {
                if ($isnoninv == 0) {
                    $cost = $this->othersClass->computecosting($data['itemid'], $data['whid'], $data['loc'], $expiry, $trno, $line, $data['iss'], $config['params']['doc'], $config['params']['companyid']);
                    if ($cost != -1) {
                        $cost2 = $cost / $factor;
                        $computedata = $this->othersClass->computestock($cost2, $disc, $isqty, $factor);
                        $this->coreFunctions->sbcupdate($this->stock, ['cost' => $cost, 'isamt' => $cost2, 'amt' => $computedata['amt'], 'ext' => $computedata['ext']], ['trno' => $trno, 'line' => $line]); //amt is also the cost

                        //CHECK BELOW COST
                        if ($this->companysetup->checkbelowcost($config['params'])) {
                            $belowcost = $this->othersClass->checkbelowcost($trno, $line, $config);
                            if ($belowcost == 1) {
                                $msg = '(' . $item[0]->barcode . ') Is this free of charge? Please check.';
                            } elseif ($belowcost == 2) {
                                $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'BELOW COST', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
                                $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
                                $this->logger->sbcwritelog($trno, $config, 'STOCK', 'BELOW COST - Line:' . $line . ' Barcode:' . $item[0]->barcode . ' Qty' . $isqty . ' Amt:' . $data['amt'] . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0', $setlog ? $this->tablelogs : '');
                                $msg = "(" . $item[0]->barcode . ") You can't issue this item/s because it's BELOW COST!!!";
                            }
                        }
                    } else {
                        $havestock = false;
                        $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
                        $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
                        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $line . ' Barcode:' . $item[0]->barcode . ' Qty' . $isqty . ' Amt:' . $data['amt'] . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0', $setlog ? $this->tablelogs : '');
                        $msg = 'OUT OF STOCK.3';
                    }
                }


                if ($this->setserveditems($refx, $linex) == 0) {
                    $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                    $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                    $this->setserveditems($refx, $linex);
                    $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
                    $return = false;
                    $msg = "(" . $item[0]->barcode . ") Qty Issued is Greater than Qty. (update)";
                }
                return ['status' => $return, 'msg' => $msg];
            } else {
                return ['status' => false, 'msg' => 'Update item Failed'];
            }
        }
    } // end function

    public function deleteallitem($config)
    {
        $trno = $config['params']['trno'];
        if ($this->companysetup->getserial($config['params'])) {
            $data2 = $this->coreFunctions->opentable('select trno,line from ' . $this->stock . ' where trno=?', [$trno]);
            foreach ($data2 as $key => $value) {
                $this->othersClass->deleteserialout($data2[$key]->trno, $data2[$key]->line);
            }
        }

        $data = $this->coreFunctions->opentable('select refx, linex, stageid from ' . $this->stock . ' where trno=? and refx<>0', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from stockinfo where trno=?', 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from costing where trno=?', 'delete', [$trno]);
        foreach ($data as $key => $value) {
            $this->setserveditems($data[$key]->refx, $data[$key]->linex);
        }
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
        return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
    }


    public function setserveditems($refx, $linex)
    {
        if ($refx == 0) {
            return 1;
        }

        $refdoc = $this->coreFunctions->getfieldvalue('transnum', 'doc', 'trno=?', [$refx]);

        $qry1 = "select stock." . $this->hqty . " from lahead as head left join lastock as 
          stock on stock.trno=head.trno where head.doc='MI' and stock.refx=" . $refx . " and stock.linex=" . $linex;

        $qry1 = $qry1 . " union all select glstock." . $this->hqty . " from glhead left join glstock on glstock.trno=
             glhead.trno where glhead.doc='MI' and glstock.refx=" . $refx . " and glstock.linex=" . $linex;

        $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
        $qty = $this->coreFunctions->datareader($qry2);

        if ($qty == '') {
            $qty = 0;
        }

        $this->coreFunctions->LogConsole($qty . 'QA');

        return $this->coreFunctions->execqry("update hprstock set siqa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
    }

    //for checking if qty > maxqty
    public function is_qty_greater_than_max($refx, $linex)
    {
        $qry1 = "select stock." . $this->hqty . " from lahead as head left join lastock as 
          stock on stock.trno=head.trno where head.doc='MI' and stock.refx=" . $refx . " and stock.linex=" . $linex;

        $qry1 = $qry1 . " union all select glstock." . $this->hqty . " from glhead left join glstock on glstock.trno=
             glhead.trno where glhead.doc='MI' and glstock.refx=" . $refx . " and glstock.linex=" . $linex;

        $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
        $qty = $this->coreFunctions->datareader($qry2);
        $maxqty = $this->get_max_qty($refx, $linex);

        if ($qty == '') {
            $qty = 0;
        }

        if ($qty > $maxqty) {
            return 1;
        } else {
            return 0;
        }
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
        $this->coreFunctions->execqry('delete from stockinfo where trno=? and line=?', 'delete', [$trno, $line]);
        $this->setserveditems($data[0]->refx, $data[0]->linex);

        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' Barcode:' . $data[0]->barcode . ' Qty:' . $data[0]->isqty . ' Amt:' . $data[0]->isamt . ' Disc:' . $data[0]->disc . ' WH:' . $data[0]->wh . ' Ext:' . $data[0]->ext);
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

    public function createdistribution($config)
    {
        $trno = $config['params']['trno'];

        $status = true;
        $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);

        $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,stock.iss * stock.cost as ext,wh.client as wh,
        ifnull(item.asset,"") as asset,ifnull(item.revenue,"") as revenue,stock.isamt,stock.iss * stock.amt as srp,stock.disc,stock.isqty,
        stock.cost,stock.iss,stock.fcost,head.projectid,client.rev,stock.rebate,head.subproject,stock.stageid,client.issubcon
          from ' . $this->head . ' as head left join ' . $this->stock . ' as stock on stock.trno=head.trno
          left join item on item.itemid=stock.itemid left join client on client.client = head.client left join client as wh on wh.clientid = stock.whid where head.trno=?';

        $stock = $this->coreFunctions->opentable($qry, [$trno]);
        $tax = 0;
        if (!empty($stock)) {
            $invacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['IN1']);
            $revacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['SA1']);
            $vat = floatval($stock[0]->tax);
            $tax1 = 0;
            $tax2 = 0;
            if ($vat !== 0) {
                $tax1 = 1 + ($vat / 100);
                $tax2 = $vat / 100;
            }
            $cur = $this->coreFunctions->getfieldvalue($this->head, 'cur', 'trno=?', [$trno]);
            foreach ($stock as $key => $value) {
                $params = [];
                $disc = $stock[$key]->isamt - ($this->othersClass->discount($stock[$key]->isamt, $stock[$key]->disc));
                if ($vat !== 0) {
                    $tax = round(($stock[$key]->ext / $tax1), 2);
                    $tax = round($stock[$key]->ext - $tax, 2);
                }

                if ($stock[$key]->revenue != '') {
                    $revacct = $stock[$key]->revenue;
                } else {
                    if ($stock[$key]->rev != '' && $stock[$key]->rev != '\\') {
                        $revacct = $stock[$key]->rev;
                    }
                }

                switch ($this->companysetup->getsystemtype($config['params'])) {
                    case 'CAIMS':
                        if ($stock[$key]->issubcon != 1) {
                            $contra = $this->coreFunctions->getfieldvalue("coa", "acno", "alias=?", ['AR7']);
                        } else {
                            $contra = $stock[$key]->contra;
                        }
                        break;
                    default:
                        $contra = $stock[$key]->contra;
                        break;
                }

                $params = [
                    'client' => $stock[$key]->wh,
                    'acno' => $contra,
                    'ext' => $stock[$key]->ext,
                    'srp' => $stock[$key]->srp,
                    'wh' => $stock[$key]->wh,
                    'date' => $stock[$key]->dateid,
                    'inventory' => $stock[$key]->asset !== '' ? $stock[$key]->asset : $invacct,
                    'revenue' => $revacct,
                    'tax' =>  $tax,
                    'discamt' => $disc * $stock[$key]->isqty,
                    'cur' => $stock[$key]->cur,
                    'forex' => $stock[$key]->forex,
                    'cost' => $stock[$key]->cost * $stock[$key]->iss,
                    'fcost' => $stock[$key]->fcost * $stock[$key]->iss,
                    'projectid' => $stock[$key]->projectid,
                    'subproject' => $stock[$key]->subproject,
                    'stage' => $stock[$key]->stageid

                ];
                $this->distribution($params, $config);
            }
        }
        if (!empty($this->acctg)) {
            $current_timestamp = $this->othersClass->getCurrentTimeStamp();
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
                $status = true;
            } else {
                $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION FAILED');
                $status = false;
            }
        }

        return $status;
    } //end function

    public function distribution($params, $config)
    {
        $entry = [];
        $forex = $params['forex'];
        $cur = $params['cur'];
        $ar = $params['srp'];
        $sales = 0;
        $companyid = $config['params']['companyid'];

        if (floatval($forex) == 0) {
            $forex = 1;
        }


        if (floatval($params['ext']) != 0) {
            $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
            $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => ($params['ext'] * $forex), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => floatval($forex) == 1 ? 0 : $params['ext'], 'fcr' => 0, 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stage']];
            $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

            $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
            $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => 0, 'cr' => $params['cost'], 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $params['fcost'], 'fdb' => 0, 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stage']];
            $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
        }
    } //end function

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
        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
        $dataparams = $config['params']['dataparams'];

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }

    public function recomputestock($head, $config)
    {
        $data = $this->openstock($head['trno'], $config);
        $data2 = json_decode(json_encode($data), true);
        $exec = true;
        foreach ($data2 as $key => $value) {
            $computedata = $this->othersClass->computestock(
                $data2[$key][$this->damt] * 1,
                $data[$key]->disc,
                round($data2[$key][$this->dqty], $this->companysetup->getdecimal('qty', $config['params'])),
                $data[$key]->uomfactor,
                0
            );
            $exec = $this->coreFunctions->execqry("update lastock set amt = " . $computedata['amt'] . " where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
        }
        return $exec;
    }
} //end class
