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


class ct
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'CONSTRUCTION INSTRUCTION';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
    public $tablenum = 'transnum';
    public $head = 'cihead';
    public $hhead = 'hcihead';
    public $stock = 'cistock';
    public $hstock = 'hcistock';
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';
    public $htablelogs = 'htransnum_log';
    private $stockselect;
    public $dqty = 'rrqty';
    public $hqty = 'qty';

    private $fields = [
        'trno', 'docno', 'dateid', 'rem', 'yourref', 'ourref', 'ourref', 'itemid', 'housemodel',
        'uom', 'qty'
    ];

    private $except = ['trno', 'dateid'];
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;
    private $reporter;
    private $helpClass;

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
            'view' => 4670,
            'edit' => 4671,
            'new' => 4672,
            'save' => 4673,
            'delete' => 4674,
            'print' => 4675,
            'lock' => 4676,
            'unlock' => 4677,
            'post' => 4678,
            'unpost' => 4679,
            'additem' => 4680,
            'edititem' => 4681,
            'deleteitem' => 4682
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
     left join trxstatus as stat on stat.line=num.statid 
     left join item on item.itemid=head.itemid
     " . $leftjoin . "
     where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " $filtersearch
     union all
     select head.trno, head.docno,  $dateid," . $status . " as stat, head.createby, head.editby, head.viewby, num.postedby,
     date(num.postdate) as postdate, head.yourref, head.ourref, item.itemname
     from " . $this->hhead . " as head 
     left join " . $this->tablenum . " as num on num.trno=head.trno 
     left join trxstatus as stat on stat.line=num.statid 
     left join item on item.itemid=head.itemid
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
            'help'

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
        $rem = 3;

        $column = [
            'action',  'rrqty', 'uom', 'rem'
        ];

        $sortcolumn = [
            'action', 'rrqty', 'uom',  'rem'
        ];

        $tab = [
            $this->gridname => [
                'gridcolumns' => $column, 'sortcolumns' => $sortcolumn
            ]
        ];

        $stockbuttons = ['save', 'delete'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $companyid = $config['params']['companyid'];

        $additem = 0;
        $quickadd = 1;
        $saveitem = 2;
        $deleteallitem = 3;
        $tbuttons = ['additem', 'quickadd', 'saveitem', 'deleteallitem'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function createHeadField($config)
    {
        $fields = ['docno', 'ditemname', 'qty', 'luom'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'ditemname.label', 'Barcode');
        data_set($col1, 'luom.lookupclass', 'piuom');
        data_set($col1, 'luom.addedparams', ['itemid']);

        $fields = ['dateid', ['yourref', 'ourref'], 'housemodel2'];
        $col2 = $this->fieldClass->create($fields);

        $fields = ['rem'];
        $col3 = $this->fieldClass->create($fields);

        return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3];
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
        $data[0]['ditemname'] = '';
        $data[0]['barcode'] = '';
        $data[0]['qty'] = '';
        $data[0]['uom'] = '';
        $data[0]['itemname'] = '';
        $data[0]['itemid'] = 0;
        $data[0]['housemodel'] = 0;
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
         head.ourref,
         left(head.dateid,10) as dateid,
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         head.yourref,
         ifnull(i.barcode,'') as barcode,ifnull(head.qty,'') as qty,ifnull(head.uom,'') as uom, ifnull(i.itemname,'') as itemname, 
         head.itemid, left(head.voiddate,10) as voiddate, ifnull(house.model,'') as housemodel2,ifnull(house.price,0) as fpricesqm ";
        $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join item as i on i.itemid=head.itemid
        left join housemodel as house on house.line = head.housemodel 
        where head.trno = ? and head.doc = ? and num.center = ?
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join item as i on i.itemid=head.itemid
        left join housemodel as house on house.line = head.housemodel 
        where head.trno = ? and head.doc = ? and num.center=? ";

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
            $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno']);
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
        $this->deleteallitem($config);
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
        $qry = "select trno from " . $this->stock . " where trno=? and qty=0 limit 1";
        $isitemzeroqty = $this->coreFunctions->opentable($qry, [$trno]);
        if (!empty($isitemzeroqty)) {
            return ['status' => false, 'msg' => 'Posting failed. Check carefully, some items have zero quantity.'];
        }
        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        if ($this->othersClass->isposted($config)) {
            return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
        }
        //for hcihead
        $qry = "insert into " . $this->hhead . "(trno,doc,docno,dateid,rem,yourref,ourref,itemid,housemodel,uom,qty,
        createdate,createby,editby,editdate,lockdate,lockuser)
        SELECT head.trno,head.doc, head.docno,head.dateid as dateid, head.rem,head.yourref, head.ourref,head.itemid,head.housemodel,
        head.uom,head.qty,
        head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser
        FROM " . $this->head . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno
        where head.trno=? limit 1";
        $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
        if ($posthead) {

            $qry = "insert into " . $this->hstock . "(trno,line,barcode,itemname,
        uom,rem,rrqty,qty,qa,void,
        encodeddate,refx,linex,amenity,subamenity,housemodel)
        SELECT trno,line,barcode,itemname, uom,rem,rrqty,qty,qa,void,
        encodeddate,refx,linex,amenity,subamenity,housemodel
        FROM " . $this->stock . " where trno =?";
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
        $qry = "select trno from " . $this->hstock . " where trno=? and  void<>0";
        $data = $this->coreFunctions->opentable($qry, [$trno]);
        if (!empty($data)) {
            return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, either already served or have item voided...'];
        }
        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        $qry = "insert into " . $this->head . "(trno,doc,docno,dateid,rem,yourref,ourref,itemid,housemodel,uom,qty,
        createdate,createby,editby,editdate,lockdate,lockuser)
         SELECT head.trno,head.doc, head.docno,head.dateid as dateid, head.rem,head.yourref, head.ourref,head.itemid,head.housemodel,
        head.uom,head.qty,
        head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser
        from (" . $this->hhead . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno)
        where head.trno=? limit 1";
        //head
        if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {

            $qry = "insert into " . $this->stock . "(trno,line,barcode,itemname,uom,rem,rrqty,qty,qa,void,
            encodeddate,refx,linex,amenity,subamenity,housemodel)
            SELECT trno,line,barcode,itemname, uom,rem,rrqty,qty,qa,void,
            encodeddate,refx,linex,amenity,subamenity,housemodel
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

    private function getstockselect($config)
    {
        $companyid = $config['params']['companyid'];
        $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);

        $sqlselect = "select
        item.itemid,
        stock.itemname,
        stock.trno,
        stock.line,
        stock.barcode,
        stock.uom,
        stock.rem,
        stock.qty as qty,
        FORMAT(stock.rrqty," . $qty_dec . ")  as rrqty,
        left(stock.encodeddate,10) as encodeddate,
        ifnull(uom.factor,1) as uomfactor,
        '' as bgcolor";
        return $sqlselect;
    }

    public function openstock($trno, $config)
    {
        $sqlselect = $this->getstockselect($config);
        $qry = $sqlselect . "
        FROM $this->stock as stock
        left join item as item on item.barcode=stock.barcode
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
        where stock.trno =?
        UNION ALL
        " . $sqlselect . "
        FROM $this->hstock as stock
        
        left join item as item on item.barcode=stock.barcode
         left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
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
        
        left join item as item on item.barcode=stock.barcode
         left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
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
                if ($data[$key]->refx == 0) {
                    $msg1 = ' Qty PO is Greater than SO Qty ';
                } else {
                    $msg2 = ' Qty PO is Greater than PR Qty ';
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
                    $msg2 = ' Qty Received is Greater than PO Qty ';
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
        $item = $this->coreFunctions->opentable("select item.itemid,0 as amt,item.disc,'' as loc, 1 as qty, uom,famt from item where barcode=?", [$barcode]);
        $item = json_decode(json_encode($item), true);
        if (!empty($item)) {
            $config['params']['barcode'] = $barcode;
            $config['params']['data'] = $item[0];
            return $this->additem('insert', $config);
        } else {
            return ['status' => false, 'msg' => 'Barcode not found.' . $barcodelength, ''];
        }
    }

    // insert and update item
    public function additem($action, $config)
    {

        $uom = $config['params']['data']['uom'];
        $barcode = $config['params']['data']['barcode'];
        $trno = $config['params']['trno'];
        $itemid = $config['params']['data']['itemid'];
        $refx = 0;
        $linex = 0;
        $rem = '';

        if (isset($config['params']['data']['rem'])) {
            $rem = $config['params']['data']['rem'];
        }
        if (isset($config['params']['data']['refx'])) {
            $refx = $config['params']['data']['refx'];
        }
        if (isset($config['params']['data']['linex'])) {
            $linex = $config['params']['data']['linex'];
        }

        if (isset($config['params']['data']['itemname'])) {
            $itemdesc = $config['params']['data']['itemname'];
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

        $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=? ";
        $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
        $factor = 1;
        if (!empty($item)) {
            $item[0]->factor = $this->othersClass->val($item[0]->factor);
            if ($item[0]->factor !== 0) $factor = $item[0]->factor;
        }
        $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));
        $computedqty = $qty * $factor;

        $data = [
            'trno' => $trno,
            'line' => $line,
            'barcode' => $barcode,
            'itemname' => $itemdesc,
            'rrqty' => $qty,
            'qty' => $computedqty,
            'uom' => $uom,
            'refx' => $refx,
            'linex' => $linex,
            'rem' => $rem
        ];

        foreach ($data as $key => $value) {
            $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        }
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        if ($uom == '') {
            $msg = 'UOM cannot be blank -' . $item[0]->barcode;
            return ['status' => false, 'msg' => $msg];
        }
        if ($action == 'insert') {
            $data['encodeddate'] = $current_timestamp;
            if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
                $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' Barcode:' . $item[0]->barcode . ' Uom:' . $uom);
                $this->loadheaddata($config);
                $row = $this->openstockline($config);
                return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.', 'line' => $line, 'reloaddata' => true];
            } else {
                return ['status' => false, 'msg' => 'Add item Failed'];
            }
        } elseif ($action == 'update') {
            $return = true;
            $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
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

    public function stockstatus($config)
    {
        switch ($config['params']['action']) {
            case 'additem':
                return $this->additem('insert', $config);
                break;
            case 'addallitem': // save all item selected from lookup
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

    public function deleteallitem($config)
    {
        $isallow = true;
        $trno = $config['params']['trno'];
        $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
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
