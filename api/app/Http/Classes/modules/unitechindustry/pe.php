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
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;

class pe
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'PRODUCTION REQUEST';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
    public $tablenum = 'transnum';
    public $head = 'prhead';
    public $hhead = 'hprhead';
    public $stock = 'prstock';
    public $hstock = 'hprstock';
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';
    public $htablelogs = 'htransnum_log';
    private $stockselect;
    public $dqty = 'rrqty';
    public $hqty = 'qty';
    public $damt = 'rrcost';
    public $hamt = 'cost';
    private $fields = [
        'trno',
        'docno',
        'dateid',
        'due',
        'client',
        'clientname',
        'yourref',
        'ourref',
        'rem',
        'terms',
        'forex',
        'cur',
        'wh',
        'address',
        'pitrno',
        'itemid',
        'qty',
        'uom',
        'color', 'weight'
    ];
    private $except = ['trno', 'dateid', 'due'];
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;
    private $reporter;
    private $helpClass;

    public $showfilterlabel = [
        ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
        ['val' => 'locked', 'label' => 'Locked', 'color' => 'red'],
        ['val' => 'posted', 'label' => 'Posted', 'color' => 'orange'],
        ['val' => 'partial', 'label' => 'Partial', 'color' => 'orange'],
        ['val' => 'complete', 'label' => 'Completed', 'color' => 'orange']
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
            'view' => 4807,
            'edit' => 4808,
            'new' => 4809,
            'save' => 4810,
            'delete' => 4811,
            'print' => 4812,
            'lock' => 4813,
            'unlock' => 4814,
            'changeamt' => 4817,
            'post' => 4815,
            'unpost' => 4816,
            'additem' => 4818,
            'edititem' => 4819,
            'deleteitem' => 4820
        );
        return $attrib;
    }


    public function createdoclisting()
    {
        $getcols = [
            'action', 'liststatus', 'listdocument', 'listdate', 'barcode', 'itemdesc', 'yourref',
            'ourref', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'
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
        $cols[$postdate]['label'] = 'Post Date';
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
        $limit = 'limit 150';
        $searchfield = [];
        $filtersearch = "";
        $search = $config['params']['search'];

        $join = '';
        $hjoin = '';
        $addparams = '';

        if (isset($config['params']['doclistingparam'])) {
            $test = $config['params']['doclistingparam'];
            if (isset($test['selectprefix'])) {
                if ($test['selectprefix'] != "") {
                    if ($test['docno'] != '') {
                        switch ($test['selectprefix']) {
                            case 'Item Code':
                                $addparams = " and (item.partno like '%" . $test['docno'] . "%' or item2.partno like '%" . $test['docno'] . "%')";
                                break;
                            case 'Item Name':
                                $addparams = " and (item.itemname like '%" . $test['docno'] . "%' or item2.itemname like '%" . $test['docno'] . "%')";
                                break;
                            case 'Model':
                                $addparams = " and (model.model_name like '%" . $test['docno'] . "%' or model2.model_name like '%" . $test['docno'] . "%')";
                                break;
                            case 'Brand':
                                $addparams = " and (brand.brand_desc like '%" . $test['docno'] . "%' or brand2.brand_desc like '%" . $test['docno'] . "%')";
                                break;
                            case 'Item Group':
                                $addparams = " and (p.name like '%" . $test['docno'] . "%' or p2.name like '%" . $test['docno'] . "%')";
                                break;
                        }
                    }

                    if (isset($test)) {
                        $join = " left join prstock on prstock.trno = head.trno
                        left join item on item.itemid = prstock.itemid left join item as item2 on item2.itemid = prstock.itemid
                        left join model_masterfile as model on model.model_id = item.model 
                        left join model_masterfile as model2 on model2.model_id = item2.model 
                        left join frontend_ebrands as brand on brand.brandid = item.brand 
                        left join frontend_ebrands as brand2 on brand2.brandid = item2.brand
                        left join projectmasterfile as p on p.line = item.projectid 
                        left join projectmasterfile as p2 on p2.line = item2.projectid ";

                        $hjoin = " left join hprstock on hprstock.trno = head.trno
                        left join item on item.itemid = hprstock.itemid left join item as item2 on item2.itemid = hprstock.itemid
                        left join model_masterfile as model on model.model_id = item.model 
                        left join model_masterfile as model2 on model2.model_id = item2.model
                        left join frontend_ebrands as brand on brand.brandid = item.brand 
                        left join frontend_ebrands as brand2 on brand2.brandid = item2.brand
                        left join projectmasterfile as p on p.line = item.projectid 
                        left join projectmasterfile as p2 on p2.line = item2.projectid ";
                        $limit = '';
                    }
                }
            }
        }


        if (isset($config['params']['search'])) {
            $searchfield = ['head.docno', 'item.barcode', 'item.itemname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        } else {
            $limit = 'limit 25';
        }

        switch ($itemfilter) {
            case 'draft':
                $condition = ' and num.postdate is null and head.lockdate is null ';
                break;

            case 'locked':
                $condition = ' and num.postdate is null and head.lockdate is not null ';
                break;

            case 'posted':
                $condition = ' and num.postdate is not null and  mihead.petrno is  null and pnhead.petrno is null ';
                break;

            case 'partial':
                $condition = 'and  mihead.petrno is not null and  stock.qa <> 0';
                break;

            case 'complete':
                $condition = 'and  mihead.petrno is not null and pnhead.petrno is not null and stock.qa = 0 ';
                break;
        }
        $qry = "select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid,head.dateid as date2,
        if(head.lockdate is not null,'LOCKED','DRAFT')  as status, date(num.postdate) as postdate,
        head.createby,head.editby,head.viewby,num.postedby,head.yourref, head.ourref,item.barcode, item.itemname as itemdesc
        from " . $this->head . " as head 
        left join " . $this->tablenum . " as num on num.trno=head.trno 
        left join item on item.itemid=head.itemid
        left join (select petrno from glhead where doc = 'MI' group by petrno) as mihead on mihead.petrno = head.trno
        left join (select petrno from glhead where doc = 'PN' group by petrno) as pnhead on pnhead.petrno = head.trno
        lEFT join (select trno, sum(qa) as qa from prstock as stock group by trno) as stock on stock.trno=head.trno
        " . $join . "
        where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " " . $filtersearch . "
        union all
        select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid,head.dateid as date2, 
        (case when mihead.petrno is not null and stock.qa <> 0 then 'PARTIAL'
              when mihead.petrno is not null and pnhead.petrno is not null and stock.qa = 0 then 'COMPLETED' else 'POSTED' end) as status, date(num.postdate) as postdate,
        head.createby,head.editby,head.viewby, num.postedby, 
        head.yourref, head.ourref,item.barcode, item.itemname as itemdesc  
        from " . $this->hhead . " as head 
        left join " . $this->tablenum . " as num on num.trno=head.trno 
        left join item on item.itemid=head.itemid
        left join (select petrno from glhead where doc = 'MI' group by petrno) as mihead on mihead.petrno = head.trno
        left join (select petrno from glhead where doc = 'PN' group by petrno) as pnhead on pnhead.petrno = head.trno
        lEFT join (select trno, sum(qa) as qa from hprstock as stock group by trno) as stock on stock.trno=head.trno
        " . $hjoin . "
        where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " " . $filtersearch . "
        order by date2 desc,docno desc $limit";
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

        if ($this->companysetup->getclientlength($config['params']) != 0) {
            array_push($btns, 'others');
        }

        $buttons = $this->btnClass->create($btns);
        $step1 = $this->helpClass->getFields(['btnnew', 'department', 'dateid', 'cswhname', 'yourref', 'csrem', 'btnsave']);
        $step2 = $this->helpClass->getFields(['btnedit', 'department', 'dateid', 'cswhname', 'yourref', 'csrem', 'btnsave']);
        $step3 = $this->helpClass->getFields(['btnadditem', 'btnquickadd', 'rrqty', 'uom', 'wh', 'rem', 'maxqty', 'btnstocksave', 'btnsaveitem']);
        $step4 = $this->helpClass->getFields(['rrqty', 'uom', 'wh', 'rem', 'maxqty', 'btnstocksave', 'btnsaveitem']);
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
            $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => $config['params']['doc'], 'title' => strtoupper($this->modulename) . '_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
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
        $pr_btnvoid_access = $this->othersClass->checkAccess($config['params']['user'], 4821);
        $columns = [
            'action', 'rrqty', 'uom', 'rem', 'sku', 'maxqty', 'itemname'
        ];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $headgridbtns = ['itemvoiding', 'viewref', 'viewdiagram'];

        if ($pr_btnvoid_access == 0) {
            unset($headgridbtns[0]);
        }

        $tab = [
            $this->gridname => [
                'gridcolumns' => $columns,
                'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'],
                'headgridbtns' => $headgridbtns
            ]
        ];

        $stockbuttons = ['save', 'delete', 'showbalance'];
        if ($this->companysetup->getiseditsortline($config['params'])) {
            array_push($stockbuttons, 'sortline');
        }

        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0]['inventory']['columns'][$rem]['style'] = 'text-align: left; width:250px;whiteSpace: normal;min-width:250px;';
        $obj[0]['inventory']['columns'][$sku]['style'] = 'text-align: left; width:250px;whiteSpace: normal;min-width:250px;';
        $obj[0]['inventory']['columns'][$sku]['label'] = 'Additional Notes';
        $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['additem', 'quickadd', 'saveitem', 'deleteallitem'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function createHeadField($config)
    {
        $fields = ['docno', 'pidocno', 'barcode', 'itemname', 'color', 'qty'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'itemname.label', 'Item: ');
        data_set($col1, 'barcode.type', 'input');
        data_set($col1, 'barcode.class', 'csbarcode sbccsreadonly');
        data_set($col1, 'itemname.class', 'csitemname sbccsreadonly');
        data_set($col1, 'itemname.readonly', true);
        data_set($col1, 'qty.label', 'Quantity: ');
        data_set($col1, 'color.label', 'Color Needed: ');
        data_set($col1, 'color.type', 'input');
        data_set($col1, 'color.class', 'cscolor');

        $fields = ['dateid', 'due', ['totalweight', 'weight'], 'rem'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'due.label', 'Date Needed');
        data_set($col2, 'rem.label', 'Remarks');
        data_set($col2, 'totalweight.label', 'Standard Weight');
        data_set($col2, 'weight.label', 'Actual Weight');

        return ['col1' => $col1, 'col2' => $col2];
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
        $data[0]['shipto'] = '';
        $data[0]['ourref'] = '';
        $data[0]['rem'] = '';
        $data[0]['terms'] = '';
        $data[0]['forex'] = 1;

        $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
        $data[0]['wh'] = $this->companysetup->getwh($params);
        $name = $this->coreFunctions->datareader("select clientname as value from client where client='" . $data[0]['wh'] . "'");
        $data[0]['whname'] = $name;
        $data[0]['address'] = '';
        $data[0]['purtype'] = '';

        $data[0]['pitrno'] = 0;
        $data[0]['itemid'] = 0;
        $data[0]['qty'] = '';
        $data[0]['uom'] = '';
        $data[0]['color'] = '';
        $data[0]['maxqty'] = '';
        $data[0]['weight'] = '';
        $data[0]['totalweight'] = '';

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
        head.wh as wh,
        warehouse.clientname as whname,
        '' as dwhname,
        left(head.due,10) as due,
        client.groupid,
        head.pitrno,pinum.docno as pidocno,
        head.itemid,
        head.qty,head.uom,head.color, i.barcode,i.itemname,head.weight,info.weight as totalweight";

        $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join transnum as pinum on pinum.trno=head.pitrno
        left join item as i on i.itemid=head.itemid
        left join iteminfo as info on info.itemid=head.itemid
        where head.trno = ? and num.center = ?
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join transnum as pinum on pinum.trno=head.pitrno
        left join item as i on i.itemid=head.itemid
        left join iteminfo as info on info.itemid=head.itemid
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
        $head = $config['params']['head'];
        $companyid = $config['params']['companyid'];
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
        //auto plot stock based on PI
        if ($data['pitrno'] != 0) {
            $this->coreFunctions->execqry("delete from prstock where trno = " . $data['trno']);
            $stockqry = "
            insert into prstock (trno,line,rrqty,qty,uom,rem,itemid,maxqty,sku)
            select " . $data['trno'] . " as trno,
            stock.line,
            stock.rrqty*" . $data['qty'] . " as rrqty,stock.qty*" . $data['qty'] . " as qty,stock.uom,
            stock.rem, i.itemid, stock.maxqty*" . $data['qty'] . " as maxqty,stock.sku
            from hpistock as stock 
            left join client as wh on wh.client=stock.wh
            left join item as i on i.barcode=stock.barcode
            where stock.trno=" . $data['pitrno'];
            $this->coreFunctions->execqry($stockqry);
        }
        if ($isupdate) {
            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
        } else {
            $data['doc'] = $config['params']['doc'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $this->coreFunctions->sbcinsert($this->head, $data);
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
        $qry = "select trno from " . $this->stock . " where trno=? and qty=0 limit 1";
        $isitemzeroqty = $this->coreFunctions->opentable($qry, [$trno]);
        if (!empty($isitemzeroqty)) {
            return ['status' => false, 'msg' => 'Posting failed. Check carefully, some items have zero quantity.'];
        }
        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        if ($this->othersClass->isposted($config)) {
            return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
        }
        //for glhead
        $qry = "insert into " . $this->hhead . "(trno,doc,docno,client,clientname,address,shipto,dateid,
        terms,rem,forex,yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,agent,wh,due,cur,
        pitrno,itemid,qty,uom,color,weight)
        SELECT head.trno,head.doc, head.docno,head.client, head.clientname, head.address,head.shipto,
        head.dateid as dateid, head.terms, head.rem, head.forex,head.yourref, head.ourref,
        head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,head.agent,head.wh,
        head.due,head.cur,head.pitrno,head.itemid,head.qty,head.uom,head.color,head.weight
        FROM " . $this->head . " as head left join cntnum on cntnum.trno=head.trno
        where head.trno=? limit 1";
        $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
        if ($posthead) {
            $qry = "insert into " . $this->hstock . "(trno,line,itemid,uom,
            whid,loc,ref,disc,cost,qty,void,rrcost,rrqty,ext,
            encodeddate,qa,encodedby,editdate,editby,refx,linex,cdqa,rem,sortline,maxqty,sku)
            SELECT trno, line, itemid, uom,whid,loc,ref,disc,cost, qty,void,rrcost, rrqty, ext,
            encodeddate,qa, encodedby,editdate,editby,refx,linex,cdqa,rem,sortline,maxqty,sku
            FROM " . $this->stock . " where trno =?";
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
        yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,wh,due,cur,pitrno,itemid,qty,uom,color,weight)
        select head.trno, head.doc, head.docno, ifnull(client.client,'') , head.clientname, head.address, head.shipto,
        head.dateid as dateid, head.terms, head.rem, head.forex, head.yourref, head.ourref, head.createdate,
        head.createby, head.editby, head.editdate, head.lockdate, head.lockuser,head.wh,head.due,head.cur,
        head.pitrno,head.itemid,head.qty,head.uom,head.color,head.weight
        from (" . $this->hhead . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno)left join client on client.client=head.client
        where head.trno=? limit 1";
        //head
        if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {

            $qry = "insert into " . $this->stock . "(
            trno,line,itemid,uom,whid,loc,ref,disc,
            cost,qty,void,rrcost,rrqty,ext,rem,encodeddate,qa,encodedby,editdate,editby,refx,linex,cdqa,sortline,maxqty,sku)
            select trno, line, itemid, uom,whid,loc,ref,disc,cost, qty,void, rrcost, rrqty,
            ext,rem, encodeddate, qa, encodedby, editdate, editby,refx,linex,cdqa,sortline,maxqty,sku
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
        } else {
            return ['status' => false, 'msg' => 'Error on Unposting Head'];
        }
    } //end function

    private function getstockselect($config)
    {
        $companyid = $config['params']['companyid'];
        $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);
        $sqlselect = "select item.brand as brand,
            ifnull(mm.model_name,'') as model,
            item.itemname,
            item.itemid,
            stock.trno,
            stock.line,
            stock.refx,
            stock.linex,
            item.barcode,
            stock.uom,
            stock.cost,
            '' as netamt,
            stock.qty as qty,
            FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,
            round((stock.cost)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as netamt,
            FORMAT(stock.rrqty," . $qty_dec . ")  as rrqty,
            FORMAT(stock.maxqty," . $qty_dec . ")  as maxqty,
            FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,
            left(stock.encodeddate,10) as encodeddate,
            stock.disc,
            case when stock.void=0 then 'false' else 'true' end as void,
            round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
            stock.ref,
            stock.whid,
            warehouse.client as wh,
            warehouse.clientname as whname,
            stock.loc,
            item.brand,
            stock.rem,
            ifnull(uom.factor,1) as uomfactor,
            '' as bgcolor,
            case when stock.void=0 then '' else 'bg-red-2' end as errcolor,
            item.subcode, item.partno, round(item.dqty, " . $this->companysetup->getdecimal('qty', $config['params']) . ") as boxcount,
            concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',ifnull(i.itemdescription,'')) as itemdescription,
            1+1 as ordernum,stock.sortline,stock.sku";
        return $sqlselect;
    }

    public function openstock($trno, $config)
    {
        $sqlselect = $this->getstockselect($config);

        $qry = $sqlselect . "
        FROM $this->stock as stock
        left join item on item.itemid=stock.itemid
        left join model_masterfile as mm on mm.model_id = item.model
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
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
        left join client as warehouse on warehouse.clientid=stock.whid 
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join iteminfo as i on i.itemid  = item.itemid
        where stock.trno =? order by sortline,line";
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


    public function stockstatusposted($config)
    {
        $action = $config['params']['action'];
        if ($action == 'stockstatusposted') {
            $action = $config['params']['lookupclass'];
        }

        switch ($action) {
            case 'updateitemvoid':
                return $this->updateitemvoid($config);
                break;
            case 'diagram':
                return $this->diagram($config);
                break;
            case 'donetodo':
                $tablenum = $this->tablenum;
                return $this->othersClass->donetodo($config, $tablenum);
                break;
            case 'navigation':
                return $this->othersClass->navigatedocno($config);
                break;
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
                break;
        }
    }



    public function getposummaryqry($config)
    {
        return "
        select head.trno as refx, stock.line as linex, head.yourref,
        stock.itemid, stock.uom, stock.disc,
        stock.rrqtY as rrqty, stock.qty as qty,
        stock.cost as cost, stock.rrcost as rrcost, stock.ext,
        stock.qa as qa, stock.whid, head.docno as ref,
        item.famt,
        FORMAT(((stock.qty-(stock.qa+stock.cdqa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,
        head.cur, head.forex,stock.sortline
        from hprhead as head
        left join hprstock as stock on stock.trno = head.trno
        left join item on item.itemid=stock.itemid
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
        where head.trno = ? and stock.qty>(stock.qa+stock.cdqa) and item.islabor=0 and stock.void=0 order by sortline,linex";
    }


    public function diagram($config)
    {
        $data = [];
        $nodes = [];
        $links = [];
        $data['width'] = 1500;
        $startx = 100;

        $qry = "select po.trno,po.docno,left(po.dateid,10) as dateid,
        CAST(concat('Total PO Amt: ',round(sum(s.ext),2)) as CHAR) as rem,s.refx
        from hpohead as po
        left join hpostock as s on s.trno = po.trno
        where s.refx = ?
        group by po.trno,po.docno,po.dateid,s.refx
        union all
        select po.trno,po.docno,left(po.dateid,10) as dateid,
        CAST(concat('Total PO Amt: ',round(sum(s.ext),2)) as CHAR) as rem,s.refx
        from pohead as po
        left join postock as s on s.trno = po.trno
        where s.refx = ?
        group by po.trno,po.docno,po.dateid,s.refx";
        $t = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno']]);
        if (!empty($t)) {
            $startx = 550;
            $a = 0;

            foreach ($t as $key => $value) {
                //PO
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
                        'color' => '#B5EAEA',
                        'details' => [$t[$key]->dateid]
                    ]
                );
                array_push($links, ['from' => $t[$key]->docno, 'to' => 'rr']);
                $a = $a + 100;

                if (floatval($t[$key]->refx) != 0) {
                    //pr
                    $qry = "select pr.docno,left(pr.dateid,10) as dateid,
                    CAST(concat('Total PR Amt: ',round(sum(s.ext),2)) as CHAR) as rem
                    from hprhead as pr left join hprstock as s on s.trno = pr.trno
                    where pr.trno = ?
                    group by pr.docno,pr.dateid";
                    $x = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
                    $poref = $t[$key]->docno;
                    if (!empty($x)) {
                        foreach ($x as $key2 => $value) {
                            data_set(
                                $nodes,
                                $x[$key2]->docno,
                                [
                                    'align' => 'left',
                                    'x' => 10,
                                    'y' => 50 + $a,
                                    'w' => 250,
                                    'h' => 80,
                                    'type' => $x[$key2]->docno,
                                    'label' => $x[$key2]->rem,
                                    'color' => '#F5FCC1',
                                    'details' => [$x[$key2]->dateid]
                                ]
                            );
                            array_push($links, ['from' => $x[$key2]->docno, 'to' => $poref]);
                            $a = $a + 100;
                        }
                    }
                }
            }

            //RR
            $qry = "
            select head.docno,
            date(head.dateid) as dateid,
            CAST(concat('Total RR Amt: ', round(sum(stock.ext),2), ' - ', 'Balance: ', round(ap.bal, 2)) as CHAR) as rem,
            head.trno
            from glhead as head
            left join glstock as stock on head.trno = stock.trno
            left join apledger as ap on ap.trno = head.trno
            where stock.refx=?
            group by head.docno, head.dateid, head.trno, ap.bal
            union all
            select head.docno,
            date(head.dateid) as dateid,
            CAST(concat('Total RR Amt: ', round(sum(stock.ext),2), ' - ', 'Balance: ', round(sum(stock.ext),2)) as CHAR) as rem,
            head.trno
            from lahead as head
            left join lastock as stock on head.trno = stock.trno
            where stock.refx=?
            group by head.docno, head.dateid, head.trno";
            $t = $this->coreFunctions->opentable($qry, [$t[0]->trno, $t[0]->trno]);
            if (!empty($t)) {
                data_set(
                    $nodes,
                    'rr',
                    [
                        'align' => 'left',
                        'x' => $startx,
                        'y' => 100,
                        'w' => 250,
                        'h' => 80,
                        'type' => $t[0]->docno,
                        'label' => $t[0]->rem,
                        'color' => '#1EAE98',
                        'details' => [$t[0]->dateid]
                    ]
                );

                foreach ($t as $key => $value) {
                    //APV
                    $rrtrno = $t[$key]->trno;
                    $apvqry = "
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
                    $apvdata = $this->coreFunctions->opentable($apvqry, [$rrtrno, $rrtrno]);
                    if (!empty($apvdata)) {
                        foreach ($apvdata as $key2 => $value2) {
                            data_set(
                                $nodes,
                                'apv',
                                [
                                    'align' => 'left',
                                    'x' => $startx + 400,
                                    'y' => 100,
                                    'w' => 250,
                                    'h' => 80,
                                    'type' => $apvdata[$key2]->docno,
                                    'label' => $apvdata[$key2]->rem,
                                    'color' => '#EC4646',
                                    'details' => [$apvdata[$key2]->dateid]
                                ]
                            );
                            array_push($links, ['from' => 'rr', 'to' => 'apv']);
                            $a = $a + 100;
                        }
                    }

                    //CV
                    if (!empty($apvdata)) {
                        $apvtrno = $apvdata[0]->trno;
                    } else {
                        $apvtrno = $rrtrno;
                    }
                    $cvqry = "
                    select head.docno, date(head.dateid) as dateid, head.trno,
                    CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
                    from glhead as head
                    left join gldetail as detail on head.trno = detail.trno
                    where detail.refx = ?
                    union all
                    select head.docno, date(head.dateid) as dateid, head.trno,
                    CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
                    from lahead as head
                    left join ladetail as detail on head.trno = detail.trno
                    where detail.refx = ?";
                    $cvdata = $this->coreFunctions->opentable($cvqry, [$apvtrno, $apvtrno]);
                    if (!empty($cvdata)) {
                        foreach ($cvdata as $key2 => $value2) {
                            data_set(
                                $nodes,
                                $cvdata[$key2]->docno,
                                [
                                    'align' => 'left',
                                    'x' => $startx + 800,
                                    'y' => 100,
                                    'w' => 250,
                                    'h' => 80,
                                    'type' => $cvdata[$key2]->docno,
                                    'label' => $cvdata[$key2]->rem,
                                    'color' => '#EAE3C8',
                                    'details' => [$cvdata[$key2]->dateid]
                                ]
                            );
                            array_push($links, ['from' => 'apv', 'to' => $cvdata[$key2]->docno]);
                            $a = $a + 100;
                        }
                    }

                    //DM
                    $dmqry = "
                    select head.docno as docno,left(head.dateid,10) as dateid,
                    CAST(concat('Total DM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem
                    from glhead as head
                    left join glstock as stock on stock.trno=head.trno
                    left join item on item.itemid = stock.itemid
                    where stock.refx=?
                    group by head.docno, head.dateid
                    union all
                    select head.docno as docno,left(head.dateid,10) as dateid,
                    CAST(concat('Total DM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem
                    from lahead as head
                    left join lastock as stock on stock.trno=head.trno
                    left join item on item.itemid=stock.itemid
                    where stock.refx=?
                    group by head.docno, head.dateid";
                    $dmdata = $this->coreFunctions->opentable($dmqry, [$rrtrno, $rrtrno]);
                    if (!empty($dmdata)) {
                        foreach ($dmdata as $key2 => $value2) {
                            data_set(
                                $nodes,
                                $dmdata[$key2]->docno,
                                [
                                    'align' => 'left',
                                    'x' => $startx + 400,
                                    'y' => 200,
                                    'w' => 250,
                                    'h' => 80,
                                    'type' => $dmdata[$key2]->docno,
                                    'label' => $dmdata[$key2]->rem,
                                    'color' => '#FFBCBC',
                                    'details' => [$dmdata[$key2]->dateid]
                                ]
                            );
                            array_push($links, ['from' => 'rr', 'to' => $dmdata[$key2]->docno]);
                            $a = $a + 100;
                        }
                    }
                }
            }
        }

        $data['nodes'] = $nodes;
        $data['links'] = $links;

        return ['status' => true, 'msg' => 'Successfully fetched.', 'data' => $data];
    }

    private function updateitemvoid($config)
    {
        $trno = $config['params']['trno'];
        $rows = $config['params']['rows'];
        foreach ($rows as $key) {
            $this->coreFunctions->execqry('update ' . $this->hstock . ' set void=1 where trno=? and line=?', 'update', [$key['trno'], $key['line']]);
        }
    } //end function

    public function updateperitem($config)
    {
        $config['params']['data'] = $config['params']['row'];
        $this->additem('update', $config);
        $data = $this->openstockline($config);
        return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    }


    public function updateitem($config)
    {
        foreach ($config['params']['row'] as $key => $value) {
            $config['params']['data'] = $value;
            $this->additem('update', $config);
        }
        $data = $this->openstock($config['params']['trno'], $config);
        return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
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
        $systype = $this->companysetup->getsystemtype($config['params']);
        $companyid = $config['params']['companyid'];
        $uom = $config['params']['data']['uom'];
        $itemid = $config['params']['data']['itemid'];
        $trno = $config['params']['trno'];
        $disc = $config['params']['data']['disc'];
        $wh = $config['params']['data']['wh'];
        $loc = $config['params']['data']['loc'];
        $void = 'false';
        $itemdesc = '';
        $maxqty = 0;
        if (isset($config['params']['data']['void'])) {
            $void = $config['params']['data']['void'];
        }
        $rem = '';
        if (isset($config['params']['data']['rem'])) {
            $rem = $config['params']['data']['rem'];
        }
        if (isset($config['params']['data']['itemname'])) {
            $itemdesc = $config['params']['data']['itemname'];
        }
        if (isset($config['params']['data']['maxqty'])) {
            $maxqty = $config['params']['data']['maxqty'];
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
        $maxqty = $this->othersClass->sanitizekeyfield('maxqty', $maxqty);


        $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
        $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
        $factor = 1;
        if (!empty($item)) {
            $item[0]->factor = $this->othersClass->val($item[0]->factor);
            if ($item[0]->factor !== 0) $factor = $item[0]->factor;
        }
        $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));
        $maxqty = round($maxqty, $this->companysetup->getdecimal('qty', $config['params']));
        $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor);


        $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
        $data = [
            'trno' => $trno,
            'line' => $line,
            'itemid' => $itemid,
            'rrcost' => $amt,
            'cost' =>  $computedata['amt'],
            'rrqty' => $qty,
            'qty' => $computedata['qty'],
            'ext' => $computedata['ext'],
            'disc' => $disc,
            'loc' => $loc,
            'uom' => $uom,
            'void' => $void,
            'rem' => $rem,
            'maxqty' => $maxqty,
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
            $data['sortline'] =  $data['line'];
            $data['encodeddate'] = $current_timestamp;
            $data['encodedby'] = $config['params']['user'];
            if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {

                $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:' . $computedata['ext'] . ' Uom:' . $uom);
                $row = $this->openstockline($config);
                return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            } else {
                return ['status' => false, 'msg' => 'Add item Failed'];
            }
        } elseif ($action == 'update') {
            return $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
        }
    } // end function

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
        $config['params']['trno'] = $config['params']['row']['trno'];
        $config['params']['line'] = $config['params']['row']['line'];
        $data = $this->openstockline($config);

        $trno = $config['params']['trno'];
        $line = $config['params']['line'];
        $qry = "delete from " . $this->stock . " where trno=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);

        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' barcode:' . $data[0]->barcode . ' Qty:' . $data[0]->rrqty . ' Amt:' . $data[0]->rrcost . ' Disc:' . $data[0]->disc . ' wh:' . $data[0]->wh . ' ext:' . $data[0]->ext);
        return ['status' => true, 'msg' => 'Item was successfully deleted.'];
    } // end function

    public function getlatestprice($config)
    {
        $barcode = $config['params']['barcode'];
        $client = $config['params']['client'];
        $center = $config['params']['center'];
        $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,disc,uom from(select head.docno,head.dateid,
          stock.rrcost as amt,stock.uom,stock.disc
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid = stock.itemid
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
        $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $client, $center, $barcode, $client]);
        if (!empty($data)) {
            return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
        } else {
            return ['status' => false, 'msg' => 'No Latest price found...'];
        }
    } // end function

    // report start to

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
        $this->logger->sbcviewreportlog($config);

        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }
} //end class
