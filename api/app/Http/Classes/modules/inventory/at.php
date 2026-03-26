<?php

namespace App\Http\Classes\modules\inventory;

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
use App\Http\Classes\sqlquery;
use Exception;

class at
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'ACTUAL COUNT';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $sqlquery;
    private $logger;
    public $expirystatus = ['readonly' => false, 'show' => true, 'showdate' => true];
    public $tablenum = 'transnum';
    public $head = 'athead';
    public $hhead = 'hathead';
    public $stock = 'atstock';
    public $hstock = 'hatstock';
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';
    public $htablelogs = 'htransnum_log';
    private $stockselect;
    public $dqty = 'rrqty';
    public $hqty = 'qty';
    public $damt = 'rrcost';
    public $hamt = 'cost';
    private $fields = ['trno', 'docno', 'dateid', 'wh', 'yourref', 'ourref', 'rem'];
    private $otherfields = ['trno', 'sizeid', 'partid'];
    private $except = ['trno', 'dateid'];
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
        $this->sqlquery = new sqlquery;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 4906,
            'edit' => 4907,
            'new' => 4908,
            'save' => 4909,
            'change' => 67,
            'delete' => 4911,
            'print' => 4912,
            'lock' => 4913,
            'unlock' => 4914,
            'changeamt' => 4920,
            'post' => 4915,
            'unpost' => 4916,
            'additem' => 4919,
            'edititem' => 4918,
            'deleteitem' => 4917,
            'viewcost' => 368,
            'viewamt' => 368
        );
        return $attrib;
    }


    public function createdoclisting()
    {
        $action = 0;
        $liststatus = 1;
        $listdocument = 2;
        $listdate = 3;
        $listclientname = 4;
        $yourref = 5;
        $ourref = 6;
        $postdate = 7;

        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'yourref', 'ourref', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $cols[$yourref]['align'] = 'text-left';
        $cols[$ourref]['align'] = 'text-left';
        $cols[$postdate]['label'] = 'Post Date';
        return $cols;
    }

    public function paramsdatalisting($config)
    {
        return [];
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
        $limit = '';
        $addparams = '';
        $join = '';
        $hjoin = '';

        switch ($itemfilter) {
            case 'draft':
                $condition = ' and num.postdate is null ';
                break;
            case 'posted':
                $condition = ' and num.postdate is not null ';
                break;
        }

        if (isset($config['params']['doclistingparam'])) {
            $test = $config['params']['doclistingparam'];
            if ($test['selectprefix'] != "") {
                switch ($test['selectprefix']) {
                    case 'Item Code':
                        $addparams = " and (item.partno like '%" . $test['docno'] . "%')";
                        break;
                    case 'Item Name':
                        $addparams = " and (item.itemname like '%" . $test['docno'] . "%' )";
                        break;
                    case 'Model':
                        $addparams = " and (model.model_name like '%" . $test['docno'] . "%' )";
                        break;
                    case 'Brand':
                        $addparams = " and (brand.brand_desc like '%" . $test['docno'] . "%' )";
                        break;
                    case 'Item Group':
                        $addparams = " and (p.name like '%" . $test['docno'] . "%')";
                        break;
                }

                if (isset($test)) {
                    $join = " left join " . $this->stock . " as stock on head.trno = stock.trno 
                              left join item on item.itemid = stock.itemid 
                              left join model_masterfile as model on model.model_id = item.model 
                              left join frontend_ebrands as brand on brand.brandid = item.brand ";

                    $hjoin = " left join " . $this->hstock . " as stock on head.trno = stock.trno 
                               left join item on item.itemid = stock.itemid 
                            left join model_masterfile as model on model.model_id = item.model 
                            left join frontend_ebrands as brand on brand.brandid = item.brand  ";
                    $limit = '';
                }
            }
        }


        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['head.docno', 'wh.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }

        if ($searchfilter == "") $limit = 'limit 150';


        $qry = "select head.trno,head.docno,wh.clientname,left(head.dateid,10) as dateid, 
                       'DRAFT' as status,head.createby,head.editby,head.viewby,num.postedby, 
                       date(num.postdate) as postdate,head.yourref, head.ourref  
                from " . $this->head . " as head 
                left join " . $this->tablenum . " as num on num.trno=head.trno 
                left join client as wh on wh.client=head.wh " . $join . " 
                where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? 
                      and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $addparams . " " . $filtersearch . "
                union all
                select head.trno,head.docno,wh.clientname,left(head.dateid,10) as dateid,'POSTED' as status,
                head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate,
                head.yourref, head.ourref  
                from " . $this->hhead . " as head 
                left join " . $this->tablenum . " as num on num.trno=head.trno 
                left join client as wh on wh.client=head.wh  " . $hjoin . " 
                where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? 
                      and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $addparams . " " . $filtersearch . "
                order by dateid desc, docno desc $limit";

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
        $step1 = $this->helpClass->getFields(['btnnew', 'cswhname', 'dateid', 'yourref', 'csrem', 'btnsave']);
        $step2 = $this->helpClass->getFields(['btnedit', 'cswhname', 'dateid', 'yourref', 'csrem', 'btnsave']);
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
        $buttons['others']['items'] = [
            'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
            'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
            'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
            'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
            // 'uploadexcel' => ['label' => 'Upload From Data Collector', 'todo' => ['type' => 'uploadexcel', 'action' => 'uploadexcel', 'lookupclass' => 'uploadexcel', 'access' => 'view']],
        ];



        if ($this->companysetup->getisshowmanual($config['params'])) {
            $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'pc', 'title' => 'PC_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
        }

        return $buttons;
    } // createHeadbutton

    public function createtab2($access, $config)
    {
        $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];
        $obj = $this->tabClass->createtab($tab, []);

        $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

        if ($this->companysetup->getistodo($config['params'])) {
            $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrytransnumtodo', 'label' => 'To Do', 'access' => 'view']];
            $objtodo = $this->tabClass->createtab($tab, []);
            $return['To Do'] = ['icon' => 'fa fa-list', 'tab' => $objtodo];
        }

        return $return;
    }


    public function createTab($access, $config)
    {
        $companyid = $config['params']['companyid'];
        $resellerid = $config['params']['resellerid'];
        $isexpiry = $this->companysetup->getisexpiry($config['params']);
        $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368);
        $systemtype = $this->companysetup->getsystemtype($config['params']);

        $column = [
            'action',
            'itemdescription',
            'rrqty',
            'uom',
            'rrcost',
            'ext',
            'wh',
            'whname',
            'loc',
            'expiry',
            'rem',
            'stock_projectname',
            'location',
            'itemname',
            'barcode'
        ];

        foreach ($column as $key => $value) {
            $$value = $key;
        }

        $headgridbtns = [];
        $tab = [
            $this->gridname => [
                'gridcolumns' => $column,
                'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'],
                'headgridbtns' => $headgridbtns
            ],
        ];

        $stockbuttons = ['save', 'delete', 'showbalance'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // 7 - ref
        $obj[0]['inventory']['columns'][$rrcost]['label'] = 'Unit Cost';

        if (!$access['changeamt']) {
            $obj[0]['inventory']['columns'][$rrcost]['readonly'] = true;
            $obj[0]['inventory']['columns'][$ext]['readonly'] = true;
        }

        if ($viewcost == '0') {
            if ($isexpiry) {
                //loc
                $obj[0]['inventory']['columns'][$loc]['readonly'] = false;
                $obj[0]['inventory']['columns'][$loc]['type'] = 'input';
                //expiry
                $obj[0]['inventory']['columns'][$expiry]['type'] = 'date';
            }
            $obj[0]['inventory']['columns'][$rrcost]['type'] = 'coldel';
            $obj[0]['inventory']['columns'][$ext]['type'] = 'coldel';
        } else {
            if ($isexpiry) {
                //loc
                $obj[0]['inventory']['columns'][$loc]['readonly'] = false;
                $obj[0]['inventory']['columns'][$loc]['type'] = 'input';
                //expiry
                $obj[0]['inventory']['columns'][$expiry]['type'] = 'date';
            }
        }

        if (!$isexpiry) {
            $obj[0]['inventory']['columns'][$loc]['type'] = 'coldel';
            $obj[0]['inventory']['columns'][$expiry]['type'] = 'coldel';
        }

        $obj[0]['inventory']['columns'][$expiry]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px';


        $obj[0]['inventory']['columns'][$rem]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px';
        $obj[0]['inventory']['columns'][$itemname]['style'] = 'width: 1%;whiteSpace: normal;min-width:1%;max-width:1%';
        $obj[0]['inventory']['columns'][$barcode]['style'] = 'width: 1%;whiteSpace: normal;min-width:1%;max-width:1%';
        $obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'coldel';


        if (!$this->companysetup->getispallet($config['params'])) {
            $obj[0]['inventory']['columns'][$location]['type'] = 'coldel';
        }

        $obj[0]['inventory']['columns'][$rrcost]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$ext]['type'] = 'coldel';

        $obj[0]['inventory']['columns'][$whname]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$itemdescription]['type'] = 'coldel';


        $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
        $obj[0]['inventory']['columns'][$barcode]['label'] = '';


        $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['readfile', 'additem', 'quickadd', 'saveitem', 'deleteallitem'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[0]['label'] = 'UPLOAD FILE';
        $obj[0]['access'] = 'additem';
        return $obj;
    }

    public function createHeadField($config)
    {
        $resellerid = $config['params']['resellerid'];
        $companyid = $config['params']['companyid'];
        $systemtype = $this->companysetup->getsystemtype($config['params']);
        $noeditdate = $this->othersClass->checkAccess($config['params']['user'], 4852);
        $fields = ['docno', 'dwhname', 'yourref'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'docno.label', 'Transaction#');

        $fields = ['dateid', 'ourref'];
        $col2 = $this->fieldClass->create($fields);

        $fields = ['rem'];
        if ($this->companysetup->getistodo($config['params'])) {
            array_push($fields, 'donetodo');
        }
        $col3 = $this->fieldClass->create($fields);

        $fields = [];
        $col4 = $this->fieldClass->create($fields);

        return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
    }



    public function createnewtransaction($docno, $params)
    {
        $data = [];
        $data[0]['trno'] = 0;
        $data[0]['docno'] = $docno;
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['wh'] = $this->companysetup->getwh($params);
        $name = $this->coreFunctions->datareader("select clientname as value from client where client='" . $data[0]['wh'] . "'");
        $data[0]['whname'] = $name;
        $data[0]['yourref'] = '';
        $data[0]['ourref'] = '';
        $data[0]['rem'] = '';
        $data[0]['sizeid'] = '';
        $data[0]['partname'] = '';
        $data[0]['partid'] = '0';
        $data[0]['stockgrp'] = '';
        $data[0]['groupid'] = '0';
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
        $qryselect = "select
         num.center,
         head.trno,
         head.docno,
         client.client,
         head.yourref,
         head.ourref,
         left(head.dateid,10) as dateid,
         head.clientname,
         head.address,
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         head.agent,
         agent.clientname as agentname,
         head.wh as wh,
         warehouse.clientname as whname,
         '' as dwhname,
         left(head.due,10) as due,
         client.groupid,
         hinfo.sizeid,
         hinfo.groupid, ifnull(stockgrp.stockgrp_name,'') as stockgrp, 
         hinfo.partid,ifnull(pmaster.part_name,'') as partname
         ";

        $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join headinfotrans as hinfo on hinfo.trno=head.trno
        left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = hinfo.groupid
        left join part_masterfile as pmaster on pmaster.part_id = hinfo.partid
        where head.trno = ? and num.center = ?
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join headinfotrans as hinfo on hinfo.trno=head.trno
        left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = hinfo.groupid
        left join part_masterfile as pmaster on pmaster.part_id = hinfo.partid
        where head.trno = ? and num.center=? ";


        $head = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);
        if (!empty($head)) {
            $stock = $this->openstock($trno, $config);
            $viewdate = $this->othersClass->getCurrentTimeStamp();
            $viewby = $config['params']['user'];
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }
            $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
            $hideobj = [];
            if ($this->companysetup->getistodo($config['params'])) {
                $btndonetodo = $this->othersClass->checkdonetodo($config, $tablenum);
                $hideobj = ['donetodo' => !$btndonetodo];
            }

            return  [
                'head' => $head,
                'griddata' => ['inventory' => $stock],
                'islocked' => $islocked,
                'isposted' => $isposted,
                'isnew' => false,
                'status' => true,
                'msg' => $msg,
                'hideobj' => $hideobj
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
        $info = [];

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
        } else {
            $data['doc'] = $config['params']['doc'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $this->coreFunctions->sbcinsert($this->head, $data);
            $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['wh']);
        }
    } // end function


    public function deletetrans($config)
    {
        $trno = $config['params']['trno'];
        $doc = $config['params']['doc'];
        $table = $config['docmodule']->tablenum;
        $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
        $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
        $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);

        $this->coreFunctions->execqry('delete from ' . $this->stock . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
        $this->othersClass->deleteattachments($config);
        $this->logger->sbcdel_log($trno, $config, $docno);
        return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
    } //end function


    public function posttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];

        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        if ($this->othersClass->isposted($config)) {
            return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
        }
        //for glhead
        $qry = "insert into " . $this->hhead . "(trno,doc,docno,client,clientname,address,shipto,dateid,
                        terms,rem,forex,yourref,ourref,createdate,createby,editby,editdate,lockdate,
                        lockuser,agent,wh,due,cur)
                SELECT head.trno,head.doc, head.docno,head.client, head.clientname, head.address,
                       head.shipto,head.dateid as dateid, head.terms, head.rem, head.forex,head.yourref, 
                       head.ourref,head.createdate,head.createby,head.editby,head.editdate, 
                       head.lockdate,head.lockuser,head.agent,head.wh,head.due,head.cur
                FROM " . $this->head . " as head 
                left join cntnum on cntnum.trno=head.trno
                where head.trno=? limit 1";
        $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
        if ($posthead) {
            $qry = "insert into " . $this->hstock . "(trno,line,itemid,uom,whid,loc,expiry,ref,
                            disc,cost,qty,void,rrcost,rrqty,ext,encodeddate,qa,encodedby,editdate,
                            editby,refx,linex,rem,palletid,locid, oqty)
                    SELECT trno, line, itemid, uom,whid,loc,expiry,ref,disc,cost, qty,void,rrcost, 
                            rrqty, ext,encodeddate,qa, encodedby,editdate,editby,refx,linex,rem,
                            palletid,locid, oqty
                    FROM " . $this->stock . " where trno =?";
            if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
                //update transnum
                $date = $this->othersClass->getCurrentTimeStamp();
                $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
                $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
                $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from stockinfotrans where trno=?", "delete", [$trno]);
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
        $qry = "select trno from " . $this->hstock . " where trno=? and void<>0";
        $data = $this->coreFunctions->opentable($qry, [$trno]);
        if (!empty($data)) {
            return ['trno' => $trno, 'status' => false, 'msg' => 'Unposting failed; The transaction`s item has been voided.'];
        }
        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        $qry = "insert into " . $this->head . "(trno,doc,docno,clientname,address,shipto,dateid,
                        terms,rem,forex,yourref,ourref,createdate,createby,editby,editdate,
                        lockdate,lockuser,wh,due,cur)
                select head.trno, head.doc, head.docno,  head.clientname, head.address, head.shipto,
                        head.dateid as dateid, head.terms, head.rem, head.forex, head.yourref, 
                        head.ourref, head.createdate,head.createby, head.editby, head.editdate, 
                        head.lockdate, head.lockuser,head.wh,head.due,head.cur
                from (" . $this->hhead . " as head 
                left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno) 
                left join client on client.client=head.client
                where head.trno=? limit 1";
        //head
        if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
            $qry = "insert into " . $this->stock . "(
                    trno,line,itemid,uom,whid,loc,expiry,ref,disc,
                    cost,qty,void,rrcost,rrqty,ext,rem,encodeddate,qa,encodedby,editdate,editby,
                    refx,linex,palletid,locid, oqty)
                    select trno, line, itemid, uom,whid,loc,expiry,ref,disc,cost, qty,void, rrcost, rrqty,
                    ext,rem, encodeddate, qa, encodedby, editdate, editby,
                    refx,linex,palletid,locid, oqty
                    from " . $this->hstock . " where trno=?";
            //stock
            if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
                $this->coreFunctions->execqry("update " . $this->stock . " set ispc=0 where trno=?", 'update', [$trno]);
                $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
                $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from hstockinfotrans where trno=?", "delete", [$trno]);
                $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
                return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
            } else {
                $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
                return ['trno' => $trno, 'status' => false, 'msg' => 'Unposting failed; there were issues with stock.'];
            }
        }
    } //end function

    private function getstockselect($config)
    {
        $companyid = $config['params']['companyid'];
        $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);

        $sqlselect = "select item.brand as brand,ifnull(mm.model_name,'') as model,item.itemid,
                        stock.trno,stock.line,stock.refx,stock.linex,item.barcode,
                        item.itemname,stock.uom,stock.cost,stock.qty as qty,
                        FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,
                        FORMAT(stock.rrqty," . $qty_dec . ")  as rrqty,
                        FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,
                        left(stock.encodeddate,10) as encodeddate,FORMAT(stock.oqty," . $qty_dec . ")  as oqty,
                        FORMAT(stock.asofqty," . $qty_dec . ")  as asofqty,stock.disc,
                        case when stock.void=0 then 'false' else 'true' end as void,
                        round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 
                        then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
                        stock.ref,stock.whid,warehouse.client as wh, warehouse.clientname as whname,
                        stock.loc,stock.expiry,item.brand,stock.rem,stock.palletid,stock.locid,
                        ifnull(pallet.name,'') as pallet,ifnull(location.loc,'') as location,
                        ifnull(uom.factor,1) as uomfactor,'' as bgcolor,
                        case when stock.void=0 then '' else 'bg-red-2' end as errcolor,
                        item.subcode, item.partno, 
                        round(item.dqty, " . $this->companysetup->getdecimal('qty', $config['params']) . ") as boxcount,
                        stock.consignee,concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',ifnull(i.itemdescription,'')) as itemdescription
                        ";
        return $sqlselect;
    }

    public function openstock($trno, $config)
    {
        ini_set('memory_limit', '-1');
        $sqlselect = $this->getstockselect($config);

        $qry = $sqlselect . "
                FROM $this->stock as stock
                left join item on item.itemid=stock.itemid
                left join model_masterfile as mm on mm.model_id = item.model
                left join pallet on pallet.line=stock.palletid
                left join location on location.line=stock.locid
                left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
                left join client as warehouse on warehouse.clientid=stock.whid
                left join frontend_ebrands as brand on brand.brandid = item.brand
                left join iteminfo as i on i.itemid  = item.itemid 
                where stock.trno =?
                UNION ALL
                " . $sqlselect . "
                FROM $this->hstock as stock
                left join item on item.itemid=stock.itemid
                left join model_masterfile as mm on mm.model_id = item.model
                left join pallet on pallet.line=stock.palletid
                left join location on location.line=stock.locid
                left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                left join client as warehouse on warehouse.clientid=stock.whid 
                left join frontend_ebrands as brand on brand.brandid = item.brand
                left join iteminfo as i on i.itemid  = item.itemid 
                where stock.trno =?  order by line ";

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
                left join item on item.itemid=stock.itemid
                left join model_masterfile as mm on mm.model_id = item.model
                left join pallet on pallet.line=stock.palletid
                left join location on location.line=stock.locid
                left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
                left join client as warehouse on warehouse.clientid=stock.whid
                left join frontend_ebrands as brand on brand.brandid = item.brand
                left join iteminfo as i on i.itemid  = item.itemid 
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
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
                break;
        }
    }


    public function stockstatusposted($config)
    {
        switch ($config['params']['action']) {
            case 'navigation':
                return $this->othersClass->navigatedocno($config);
                break;
            case 'readfile':
                return $this->readfile($config);
                break;
            case 'donetodo':
                $tablenum = $this->tablenum;
                return $this->othersClass->donetodo($config, $tablenum);
                break;
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
                break;
        }
    }

    public function readfile($config)
    {
        ini_set('max_execution_time', -1);
        ini_set('memory_limit', '-1');

        $trno = $config['params']['trno'];
        $status = true;
        $msg = '';

        $wh = $this->coreFunctions->getfieldvalue($this->head, "wh", "trno=?", [$trno]);
        $csv = $config['params']['csv'];
        $arrcsv = explode("\r\n", $csv);

        $index = 0;

        $rowdata = [];

        try {
            foreach ($arrcsv as $arr) {
                $rowdata = $arr;
                $newarr = explode(",", $arr);

                if (!isset($newarr[1])) {
                    goto NextHere;
                }

                $barcode = (string)$newarr[1];

                if (!isset($newarr[2])) {
                    $qty = 0;
                } else {
                    $qty = $this->othersClass->val($newarr[2]);
                    // if ($qty == '' || $qty == null) {
                    //     $qty = 0;
                    // }
                }

                $item = $this->coreFunctions->opentable("select item.itemid,0 as amt,item.disc,'' as loc,'" . $wh . "' as wh, " . $qty . " as qty, uom from item where barcode='" . $barcode . "'");
                $item = json_decode(json_encode($item), true);

                if (count($item) == 0) {
                    $msg .= 'Missing item ' . $barcode . '<br>';
                    $status = false;
                    goto NextHere;
                }

                $line = $this->coreFunctions->getfieldvalue($this->stock, "line", "trno=? and itemid=?", [$trno, $item[0]['itemid']], '', true);

                $type = 'update';
                if ($line != 0) {
                    $config['params']['data'] = $item[0];
                    $config['params']['line'] = $line;
                    $config['params']['data']['line'] = $line;
                    $config['params']['data']['rrcost'] = 0;

                    $rrqty = $this->coreFunctions->getfieldvalue($this->stock, $this->dqty, "trno=? and line=?", [$trno, $line]);
                    $config['params']['data'][$this->dqty] =  $rrqty + $qty;
                } else {
                    $config['params']['data'] = $item[0];
                    $config['params']['data']['line'] = 0;
                    $type = 'insert';
                }

                $return = $this->additem($type, $config);
                // $this->coreFunctions->LogConsole(json_encode($config['params']['data']));
                NextHere:
                $index += 1;
                // $this->coreFunctions->LogConsole(json_encode("index: " . $index));
            }
        } catch (Exception $e) {
            $status = false;
            $msg = 'Error in row ' . $index . ' ' . json_encode($rowdata) . '.  ' . $e->getMessage() . '<br>';
            // $rowdata
        }


        $data = $this->openstock($trno, $config);

        if ($msg == '') {
            $msg = 'File uploaded';
        }

        return ['status' => $status, 'reloadgriddata' => true, 'msg' => $msg, 'griddata' => ['inventory' => $data]];
    }



    public function updateperitem($config)
    {
        $config['params']['data'] = $config['params']['row'];
        $isupdate = $this->additem('update', $config);
        $data = $this->openstockline($config);
        $data2 = json_decode(json_encode($data), true);

        $msg1 = '';
        $msg2 = '';

        $msg = '';
        if (isset($isupdate['msg'])) {
            if ($isupdate['msg'] != '') {
                $msg = $isupdate['msg'];
            }
        }

        foreach ($data2 as $key => $value) {
            if ($data2[$key][$this->dqty] == 0) {
                $data[$key]->errcolor = 'bg-red-2';
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
        $msg = '';
        foreach ($config['params']['row'] as $key => $value) {
            $config['params']['data'] = $value;
            $update = $this->additem('update', $config);
            if ($msg != '') {
                if (isset($update['msg'])) {
                    $msg = $msg . ' ' . $update['msg'];
                }
            } else {
                if (isset($update['msg'])) {
                    $msg = $update['msg'];
                }
            }
        }
        $data = $this->openstock($config['params']['trno'], $config);
        $data2 = json_decode(json_encode($data), true);
        $isupdate = true;
        $msg1 = '';
        $msg2 = '';
        foreach ($data2 as $key => $value) {
            if ($data2[$key][$this->dqty] == 0) {
                $data[$key]->errcolor = 'bg-red-2';
            }
        }

        return ['inventory' => $data, 'status' => true, 'msg' => $msg];
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
        $item = $this->coreFunctions->opentable("select item.itemid,0 as amt,item.disc,'' as loc,'" . $wh . "' as wh, 1 as qty, uom from item where barcode=?", [$barcode]);
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
        $companyid = $config['params']['companyid'];
        $systemtype = $this->companysetup->getsystemtype($config['params']);
        $uom = $config['params']['data']['uom'];
        $itemid = $config['params']['data']['itemid'];
        $trno = $config['params']['trno'];
        $disc = isset($config['params']['data']['disc']) ? $config['params']['data']['disc'] : '';
        $wh = $config['params']['data']['wh'];
        $loc = $config['params']['data']['loc'];
        $void = 'false';

        $getqoh = isset($config['params']['getqoh']) ? true : false;

        if (isset($config['params']['data']['void'])) {
            $void = $config['params']['data']['void'];
        }

        $refx = 0;
        $linex = 0;
        $rem = '';
        $expiry = '';
        $palletid = 0;
        $locid = 0;
        $consignee = "";
        $oqty = 0;

        if (isset($config['params']['data']['oqty'])) {
            $oqty = $config['params']['data']['oqty'];
        }

        if (isset($config['params']['data']['expiry'])) {
            $expiry = $config['params']['data']['expiry'];
        }

        if (isset($config['params']['data']['rem'])) {
            $rem = $config['params']['data']['rem'];
        }
        if (isset($config['params']['data']['refx'])) {
            $refx = $config['params']['data']['refx'];
        }
        if (isset($config['params']['data']['linex'])) {
            $linex = $config['params']['data']['linex'];
        }


        if (isset($config['params']['data']['palletid'])) {
            $palletid = $config['params']['data']['palletid'];
        }

        if (isset($config['params']['data']['consignee'])) {
            $consignee = $config['params']['data']['consignee'];
        }

        if (isset($config['params']['data']['locid'])) {
            $locid = $config['params']['data']['locid'];
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
            $qty = $config['params']['data']['qty'];
        } elseif ($action == 'update') {
            $config['params']['line'] = $config['params']['data']['line'];
            $line = $config['params']['data']['line'];
            $amt = $config['params']['data'][$this->damt];
            $qty = $config['params']['data'][$this->dqty];
            $config['params']['line'] = $line;
        }
        $amt = $this->othersClass->sanitizekeyfield('amt', $amt);
        $qty = $this->othersClass->sanitizekeyfield('qty', $qty);
        $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
        $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
        $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
        $factor = 1;
        if (!empty($item)) {
            $item[0]->factor = $this->othersClass->val($item[0]->factor);
            if ($item[0]->factor !== 0) $factor = $item[0]->factor;
        }
        $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));
        $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor);

        $data = [
            'trno' => $trno,
            'line' => $line,
            'itemid' => $itemid,
            'rrcost' => $amt,
            'cost' => number_format($computedata['amt'], $this->companysetup->getdecimal('price', $config['params']), '.', ''),
            'rrqty' => $qty,
            'qty' => $computedata['qty'],
            'ext' => number_format($computedata['ext'], $this->companysetup->getdecimal('currency', $config['params']), '.', ''),
            'disc' => $disc,
            'whid' => $whid,
            'loc' => $loc,
            'uom' => $uom,
            'void' => $void,
            'refx' => $refx,
            'linex' => $linex,
            'rem' => $rem,
            'palletid' => $palletid,
            'locid' => $locid,
            'expiry' => $expiry,
            'oqty' => $oqty,
        ];

        foreach ($data as $key => $value) {
            $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        }
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $data['editdate'] = $current_timestamp;
        $data['editby'] = $config['params']['user'];

        if ($uom == '') {
            $msg = 'UOM cannot be blank -' . $item[0]->barcode;
            return ['status' => false, 'msg' => $msg];
        }

        if ($action == 'insert') {
            $data['encodeddate'] = $current_timestamp;
            $data['encodedby'] = $config['params']['user'];
            // $data['sortline'] =  $data['line'];

            if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
                $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' Barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' WH:' . $wh . ' Ext:' . $computedata['ext'] . ' Uom:' . $uom);
                $row = $this->openstockline($config);
                return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            } else {
                return ['status' => false, 'msg' => 'Add item Failed'];
            }
        } elseif ($action == 'update') {
            $return = ['status' => true, 'msg' => 'Successfully updated.'];
            if ($this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]) != 1) {
                $return = ['status' => false, 'msg' => 'Update item failed'];
            }

            return $return;
        }
    } // end function



    public function deleteallitem($config)
    {
        $isallow = true;
        $trno = $config['params']['trno'];
        $data = $this->coreFunctions->opentable('select refx,linex from ' . $this->stock . ' where trno=? and refx<>0', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$trno]);
        foreach ($data as $key => $value) {
        }
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
        $qry = "delete from " . $this->stock . " where trno=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
        $this->coreFunctions->execqry('delete from stockinfotrans where trno=? and line=?', 'delete', [$trno, $line]);
        if ($data[0]->refx !== 0) {
        }
        $data = json_decode(json_encode($data), true);
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' Barcode:' . $data[0]['barcode'] . ' Qty:' . $data[0]['rrqty'] . ' Amt:' . $data[0]['rrcost'] . ' Disc:' . $data[0]['disc'] . ' WH:' . $data[0]['wh'] . ' Ext:' . $data[0]['ext']);
        return ['status' => true, 'msg' => 'Item was successfully deleted.'];
    } // end function

    public function getlatestprice($config)
    {
        $barcode = $config['params']['barcode'];
        $client = $config['params']['client'];
        $center = $config['params']['center'];
        $trno = $config['params']['trno'];

        $data = $this->othersClass->getlatestcostTS($config, $barcode, $client, $center, $trno);

        if (!empty($data['data'])) {
            if ($this->companysetup->getisdefaultuominout($config['params'])) {
                $data['data'][0]->docno = 'UOM';
                $defuom = $this->coreFunctions->datareader("select ifnull(uom.uom,'') as value from item left join uom on uom.itemid=item.itemid and uom.isdefault = 1 where item.barcode=?", [$barcode]);
                $this->coreFunctions->LogConsole('Def' . $defuom);
                if ($defuom != "") {
                    $data['data'][0]->uom = $defuom;
                    if ($this->companysetup->getisrecalcamtchangeuom($config['params'])) {
                        if ($data['data'][0]->amt != 0) {
                            $data['data'][0]->amt = $data['data'][0]->amt * ($this->coreFunctions->datareader("select uom.factor as value from item left join uom on uom.itemid=item.itemid and uom.uom = '" . $defuom . "' where item.barcode=?", [$barcode]));
                        } else {
                            $data['data'][0]->amt = $this->coreFunctions->datareader("select (item.amt*ifnull(uom.factor,1)) as value from item left join uom on uom.itemid=item.itemid and uom.uom = '" . $defuom . "' where item.barcode=?", [$barcode]);
                        }
                    }
                }
            } else {
                if ($this->companysetup->getisuomamt($config['params'])) {
                    $data['data'][0]->docno = 'UOM';
                    $data['data'][0]->amt = $this->coreFunctions->datareader("select ifnull(uom.amt,0) as value from item left join uom on uom.itemid=item.itemid and uom.uom=item.uom where item.barcode=?", [$barcode]);
                }
            }
        }

        if (!empty($data['data'])) {
            return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data['data']];
        } else {
            return ['status' => false, 'msg' => 'No Latest price found...'];
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
        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }
} //end class
