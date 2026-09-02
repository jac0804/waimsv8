<?php

namespace App\Http\Classes\modules\detachmentpayroll;

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
use Exception;

class dd
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'DDO ISSUANCE';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    public $expirystatus = ['readonly' => true, 'show' => true, 'showdate' => false];
    public $tablenum = 'hrisnum';
    public $statlogs = 'transnum_stat';
    public $head = 'ddhead';
    public $hhead = 'hddhead';
    public $detail = 'dddetail';
    public $hdetail = 'hdddetail';
    // public $firearms = 'ddfirearms';

    public $tablelogs = 'hrisnum_log';
    public $tablelogs_del = 'del_hrisnum_log';
    private $stockselect;
    public $fields = [
        'trno',
        'docno',
        'dateid',
        'client',
        'clientname'
    ];

    public $except = ['trno', 'dateid', 'due'];
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
            'view' => 5971,
            'edit' => 5972,
            'new' => 5973,
            'save' => 5974,
            // 'additem' => 0,
            // 'edititem' => 0,
            'deleteitem' => 5975,
            'print' => 5976
        );
        return $attrib;
    }


    public function createdoclisting($config)
    {
        $companyid = $config['params']['companyid'];

        $getcols = ['action',  'listdocument', 'divname', 'listcreateby', 'createdate'];

        foreach ($getcols as $key => $value) {
            $$value = $key;
        }

        $stockbuttons = ['view'];


        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

        $cols[$action]['style'] = 'width:50px;whiteSpace: normal;min-width:50px;';
        $cols[$divname]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[$divname]['label'] = 'Detachment';
        $cols = $this->tabClass->delcollisting($cols);
        return $cols;
    }

    public function paramsdatalisting($config)
    {
        $fields = [];
        $col1 = [];
        return ['status' => true, 'data' => [], 'txtfield' => ['col1' => $col1]];
    }

    public function loaddoclisting($config)
    {
        $date1 = date('Y-m-d', strtotime($config['params']['date1']));
        $date2 = date('Y-m-d', strtotime($config['params']['date2']));
        $itemfilter = $config['params']['itemfilter'];
        $doc = $config['params']['doc'];
        $center = $config['params']['center'];
        $condition = '';
        $laext = '';
        $glext = '';

        $lfield = '';
        $gfield = '';

        $orderby = "order by dateid desc, docno desc";

        $limit = "limit 150";
        $searchfield = [];
        $filtersearch = "";
        $search = $config['params']['search'];
        $lstatus = " case when num.postdate is null then 'DRAFT' else 'POSTED' end";

        if (isset($config['params']['search'])) {
            $searchfield = ['head.docno', 'head.divid', 'head.createby', 'head.editby', 'head.viewby'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
            $limit = "";
        }

        switch ($itemfilter) {
            case 'draft':
                $condition = 'and num.postdate is null ';
                break;
            case 'posted':
                $condition = ' and num.postdate is not null ';
                break;
        }

        $qry = "select head.trno,head.docno,left(head.dateid,10) as dateid, " . $lstatus . " as status,num.postedby,
     head.createby, head.editby, head.viewby, division.divname, division.divcode, division.divid  $lfield
     from " . $this->head . " as head left join " . $this->tablenum . " as num on num.trno=head.trno 
     left join division  on division.divid = head.divid  
     where  num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
     union all
     select head.trno,head.docno,left(head.dateid,10) as dateid,'POSTED' as status, num.postedby,
      head.createby, head.editby, head.viewby,  division.divname, division.divcode, division.divid  $gfield
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num on num.trno=head.trno
    left join division  on division.divid = head.divid  
    where  num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
    $orderby " . $limit;

        $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
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

        $head = [];
        $qry = "select head.trno, head.docno,head.dateid, division.divid, division.divname, division.divcode as division 
        from ddhead as head
        left join division on division.divid = head.divid
        where head.trno = ?
        union all
        select head.trno, head.docno,head.dateid, division.divid, division.divname, division.divcode as division
        from hddhead as head
        left join division on division.divid = head.divid
        where head.trno = ?";

        $head = $this->coreFunctions->opentable($qry, [$trno, $trno]);

        if (!empty($head)) {
            $stock = [];
            // $stock = $this->openstock($trno, $config);
            $viewdate = $this->othersClass->getCurrentTimeStamp();
            $viewby = $config['params']['user'];
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }
            $postdate = $this->coreFunctions->datareader("select postdate as value from " . $this->tablenum . " where trno = ?", [$trno]);
            $postdate = $postdate != null ? true : false;

            $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
            $hidetabbtn = [];
            $clickobj = [];

            $hideobj = [];
            return  [
                'head' => $head,
                'griddata' => ['inventory' => $stock],
                'isposted' => $postdate,
                'isnew' => false,
                'status' => true,
                'msg' => $msg,
                'clickobj' => $clickobj,
                'hidetabbtn' => $hidetabbtn,
                'hideobj' => $hideobj
            ];
        } else {
            $head[0]['trno'] = 0;
            $head[0]['docno'] = '';
            return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
        }
    }

    public function createHeadbutton($config)
    {
        $btns = array(
            'load',
            'new',
            'save',
            'delete',
            'cancel',
            'print'
        );
        $buttons = $this->btnClass->create($btns);
        $step1 = $this->helpClass->getFields(['btnnew', 'customer', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
        $step2 = $this->helpClass->getFields(['btnedit', 'customer', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
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

        $buttons['others']['items'] = [
            'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
            'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
            'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
            'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
        ];

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

        $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

        return $return;
    }



    public function createnewtransaction($docno, $params)
    {
        $data = [];
        $data[0]['trno'] = 0;
        $data[0]['docno'] = $docno;
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['divid'] = 0;
        $data[0]['division'] = '';
        $data[0]['divname'] = '';
        return $data;
    }

    public function createHeadField($config)
    {
        $fields = ['docno', 'ddivname', ['rate', 'cola', 'lblendingbal']];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'ddivname.type', 'lookup');
        data_set($col1, 'ddivname.lookupclass', 'lookupempdivision');
        data_set($col1, 'ddivname.action', 'lookupempdivision');
        data_set($col1, 'ddivname.class', 'sbccsreadonly');
        data_set($col1, 'ddivname.label', 'Detachment');
        data_set($col1, 'rate.class', 'sbccsreadonly');
        data_set($col1, 'cola.class', 'sbccsreadonly');
        data_set($col1, 'rate.readonly', true);
        data_set($col1, 'cola.readonly', true);
        data_set($col1, 'lblendingbal.type', 'input');
        data_set($col1, 'lblendingbal.readonly', true);
        data_set($col1, 'lblendingbal.class', 'sbccsreadonly');



        return ['col1' => $col1];
    }



    public function createTab($access, $config)
    {
        $fields = ['creditinfo'];
        $col1 = $this->fieldClass->create($fields);
        $iscreateversion = $this->companysetup->getiscreateversion($config['params']);
        $so_btnvoid_access = $this->othersClass->checkAccess($config['params']['user'], 3593);
        $iskgs = $this->companysetup->getiskgs($config['params']);



        $column = ['action' ];
        foreach ($column as $key => $value) {
            $$value = $key;
        }
        $headgridbtns = ['viewref', 'viewdiagram'];


        $tab = [
            $this->gridname => [
                'gridcolumns' => $column,
                'headgridbtns' => $headgridbtns
            ],
        ];

        $stockbuttons = ['save', 'delete', 'showbalance'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0]['inventory']['columns'][$uom]['style'] = 'width:70px;whiteSpace: normal;min-width:70px; text-align:left;';
        $obj[0]['inventory']['columns'][$ext]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; text-align:right;';
        $obj[0]['inventory']['columns'][$rem]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';


        $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['additem', 'quickadd', 'saveitem', 'deleteallitem', 'pendingso'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }
} //end class
