<?php

namespace App\Http\Classes\modules\rc952c55ab9eb85660b7cab413fa7c803;

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
use App\Http\Classes\headClass;
use Illuminate\Support\Facades\Storage;
use App\Http\Classes\sbcscript\sbcscript;
use Exception;



class po
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'PURCHASE ORDER';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
    public $tablenum = 'transnum';
    public $head = 'pohead';
    public $hhead = 'hpohead';
    public $stock = 'postock';
    public $hstock = 'hpostock';
    public $tablelogs = 'transnum_log';
    public $statlogs = 'transnum_stat';
    public $tablelogs_del = 'del_transnum_log';
    public $htablelogs = 'htransnum_log';
    public $infohead = 'headinfotrans';
    public $hinfohead = 'hheadinfotrans';
    private $stockselect;
    private $sbcscript;
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
        'projectid',
        'subproject',
        'branch',
        'deptid',
        'tax',
        'vattype',
        'empid',
        'sotrno',
        'billid',
        'shipid',
        'billcontactid',
        'shipcontactid',
        'revision',
        'rqtrno',
        'deldate',
        'deladdress',
        'whreceiver',
        'insurance',
        'ewtrate',
        'ewt',
        'projectid',
        'phaseid',
        'modelid',
        'blklotid',
        'amenityid',
        'subamenityid',
        'expiryid',
        'isfa'
    ];
    private $except = ['trno', 'dateid', 'due'];
    private $blnfields = ['isfa'];
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;
    private $reporter;
    private $helpClass;
    private $headClass;


    public $showfilterlabel = [
        ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary']
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
        $this->headClass = new headClass;
        $this->sbcscript = new sbcscript;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 63,
            'edit' => 64,
            'new' => 65,
            'save' => 66,
            // 'change' => 67, remove change doc
            'delete' => 68,
            'print' => 69,
            'lock' => 70,
            'unlock' => 71,
            'changeamt' => 72,
            'post' => 73,
            'unpost' => 74,
            'additem' => 808,
            'edititem' => 809,
            'deleteitem' => 810,
            'viewamt' => 843,
            'prbutton' => 2548,
            'addcditem' => 4192,
            'viewcost' => 368
        );
        return $attrib;
    }


    public function createdoclisting($config)
    {
        ini_set('max_execution_time', -1);
        $companyid = $config['params']['companyid'];
        $getcols = ['action', 'lblstatus', 'listdocument', 'listdate', 'listclientname', 'yourref', 'ourref', 'total', 'rem', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];


        $stockbuttons = ['view', 'diagram'];
        foreach ($getcols as $key => $value) {
            $$value = $key;
        }

        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

        $cols[$action]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$lblstatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$listclientname]['style'] = 'width:350px;whiteSpace: normal;min-width:350px;';
        $cols[$yourref]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[$yourref]['align'] = 'text-left';
        $cols[$ourref]['align'] = 'text-left';
        $cols[$total]['label'] = 'Grand Total';


        $cols[$rem]['type'] = 'coldel';
        $cols[$total]['type'] = 'coldel';

        $cols[$postdate]['label'] = 'Post Date';


        $this->showfilterlabel = [
            ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
            ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary'],
            ['val' => 'all', 'label' => 'All', 'color' => 'primary']
        ];


        $cols = $this->tabClass->delcollisting($cols);

        return $cols;
    }

    public function paramsdatalisting($config)
    {
        $companyid = $config['params']['companyid'];
        return ['status' => true, 'data' => [], 'txtfield' => ['col1' => []]];
    }

    public function loaddoclisting($config)
    {
        ini_set('max_execution_time', -1);
        $date1 = date('Y-m-d', strtotime($config['params']['date1']));
        $date2 = date('Y-m-d', strtotime($config['params']['date2']));
        $itemfilter = $config['params']['itemfilter'];
        $doc = $config['params']['doc'];
        $center = $config['params']['center'];
        $condition = '';
        $status = "";
        $ustatus = "";
        $limit = '';

        $searchfield = [];
        $filtersearch = "";
        $search = $config['params']['search'];

        $join = '';
        $hjoin = '';
        $addparams = '';


        /////
        $filterstat = " and num.statid = 0";
        if ($this->companysetup->linearapproval($config['params'])) {
            $itemfilter = isset($config['params']['doclistingparam']['typecode']) ? $config['params']['doclistingparam']['typecode'] : $itemfilter;
            $user = $config['params']['user'];
            $userid = $this->coreFunctions->getfieldvalue("useraccess", "userid", "username=?", [$user]);
            if ($userid != 0) {
                $qry = "select s.isapprover as value
                from approversetup as s
                left join approverdetails as d on d.appline=s.line
                left join useraccess as u on u.username=d.approver
                where u.userid=? and s.doc=?";

                $isapprover = $this->coreFunctions->datareader($qry, [$userid, $doc]);
                if ($isapprover == 1) {
                    $itemfilter = isset($config['params']['doclistingparam']['typecode']) ? $config['params']['doclistingparam']['typecode'] : 'forapproval';
                }
            }
        }
        //////

        $ustatus = "'DRAFT'";
        $companyid = $config['params']['companyid'];
        $homejoin = "";
        $addf = "";
        $grp = "";

        $dateid = "left(head.dateid,10) as dateid";
        $status = "stat.status";
        if ($search != "") $limit = 'limit 150';
        $orderby = "order by dateid desc, docno desc";


        $leftjoin = "";
        $leftjoin_posted = "";
        switch ($itemfilter) {
            case 'draft':
                $condition = " and num.postdate is null and head.lockdate is null $filterstat";
                break;

            case 'pending':
                $leftjoin = ' left join postock as stock on stock.trno=head.trno';
                $leftjoin_posted = ' left join hpostock as stock on stock.trno=head.trno';
                $condition = ' and stock.qty>stock.qa and stock.void=0 and num.postdate is not null';
                break;

            case 'forapproval':
                $condition .= " and num.postdate is null and head.lockdate is null and num.statid=10 
                        and num.appuser='" . $config['params']['user'] . "'";
                $status = "'FOR APPROVAL'";
                $ustatus = "'for approval'";
                break;
            case 'approved':
                $condition .= " and num.postdate is null and head.lockdate is null and num.statid=36";
                $status = "'APPROVED'";
                $ustatus = "'Approved'";
                break;

            case 'locked':
                $condition = ' and head.lockdate is not null and num.postdate is null ';
                $status = "'LOCKED'";
                $ustatus = "'LOCKED'";
                break;

            case 'complete':
                $condition = ' and num.statid = 7 ';
                break;

            case 'posted':
                $condition = ' and num.postdate is not null';
                break;
            case 'all':
                $ustatus = " case when num.statid=36 then 'Approved' when num.statid=10 then 'For approval' else 'Draft' end ";
                break;
        }

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
                        $join = " left join postock on postock.trno = head.trno
          left join item on item.itemid = postock.itemid left join item as item2 on item2.itemid = postock.itemid
          left join model_masterfile as model on model.model_id = item.model 
          left join model_masterfile as model2 on model2.model_id = item2.model 
          left join frontend_ebrands as brand on brand.brandid = item.brand 
          left join frontend_ebrands as brand2 on brand2.brandid = item2.brand
          left join projectmasterfile as p on p.line = item.projectid 
          left join projectmasterfile as p2 on p2.line = item2.projectid ";

                        $hjoin = " left join hpostock on hpostock.trno = head.trno
          left join item on item.itemid = hpostock.itemid left join item as item2 on item2.itemid = hpostock.itemid
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
            $searchfield = ['head.docno', 'head.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];

            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }

        $qry = "select head.trno,head.docno,head.clientname,$dateid,case ifnull(head.lockdate,'') when '' then " . $ustatus . " else 'Locked' end as stat,head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate,head.yourref, head.ourref, head.rem, (select format(sum(ext), " . $this->companysetup->getdecimal('price', $config['params']) . ") from " . $this->stock . " where trno=head.trno) as total  $addf 
     from " . $this->head . " as head 
     left join " . $this->tablenum . " as num on num.trno=head.trno 
     left join trxstatus as stat on stat.line=num.statid 
     " . $leftjoin . "
     " . $join . "
     " . $homejoin . "
     where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " " . $filtersearch . "
     group by head.trno, head.docno, head.clientname, head.dateid, stat.status,
          head.createby, head.editby, head.viewby, num.postedby,
          num.postdate, head.yourref, head.ourref,stat.line,head.lockdate, head.rem,num.statid  $grp
     union all
     select head.trno,head.docno,head.clientname,$dateid," . $status . " as stat,head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate,head.yourref, head.ourref, head.rem, (select format(sum(ext), " . $this->companysetup->getdecimal('price', $config['params']) . ") from " . $this->hstock . " where trno=head.trno) as total  $addf 
     from " . $this->hhead . " as head 
     left join " . $this->tablenum . " as num on num.trno=head.trno 
     left join trxstatus as stat on stat.line=num.statid 
     " . $leftjoin_posted . "
     " . $hjoin . "
    " . $homejoin . "
     where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " " . $filtersearch . "
     group by head.trno, head.docno, head.clientname, head.dateid, stat.status, head.createby, head.editby, head.viewby, num.postedby, num.postdate, head.yourref, head.ourref,stat.line ,head.lockdate, head.rem  $grp
    $orderby $limit";
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
            // 'lock',
            // 'unlock',
            'logs',
            'edit',
            'backlisting',
            'toggleup',
            'toggledown',
            'help',
            'others'
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

        $buttons['others']['items']['first'] =  ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']];
        $buttons['others']['items']['prev'] =  ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']];
        $buttons['others']['items']['next'] = ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']];
        $buttons['others']['items']['last'] = ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']];


        if ($this->companysetup->getisshowmanual($config['params'])) {
            $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'po', 'title' => 'PO_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
        }



        return $buttons;
    } // createHeadbutton

    public function createtab2($access, $config)
    {
        $companyid = $config['params']['companyid'];

        $billshipdefault = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewbillingshipping']];
        $instructiontab = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewinstructiontab']];

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
        $viewrrcost = $this->othersClass->checkAccess($config['params']['user'], 843);


        $column = ['action', 'barcode', 'rrqty', 'uom',  'itemname', 'rrcost', 'disc', 'ext', 'qa', 'rem', 'ref', 'void'];
        $sortcolumn = ['action',  'barcode', 'rrqty', 'uom',  'itemname', 'rrcost', 'disc', 'ext', 'qa', 'rem', 'ref', 'void'];

        $headgridbtns = ['itemvoiding', 'viewref', 'viewitemstockinfo', 'viewdiagram'];

        foreach ($column as $key => $value) {
            $$value = $key;
        }

        foreach ($sortcolumn as $key => $value) {
            $$value = $key;
        }

        $tab = [
            $this->gridname => [
                'gridcolumns' => $column,
                'sortcolumns' => $sortcolumn,
                'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'],
                'headgridbtns' => $headgridbtns
            ]

        ];




        $stockbuttons = ['delete'];

        if ($this->companysetup->getiseditsortline($config['params'])) {
            array_push($stockbuttons, 'sortline');
        }

        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0]['inventory']['columns'][$action]['style'] = 'width: 50px;whiteSpace: normal;min-width:50px;max-width:50px';


        $obj[0]['inventory']['columns'][$barcode]['label'] = 'Barcode';
        $obj[0]['inventory']['columns'][$barcode]['type'] = 'label';
        $obj[0]['inventory']['columns'][$barcode]['style'] = 'text-align: left; width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;';
        $obj[0]['inventory']['columns'][$rrqty]['checkfield'] = 'void';
        $obj[0]['inventory']['columns'][$rrcost]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px';

        $obj[0]['inventory']['columns'][$itemname]['type'] = 'label';
        $obj[0]['inventory']['columns'][$itemname]['label'] = 'Itemname';



        if ($viewrrcost == 0) {
            $obj[0]['inventory']['columns'][$rrcost]['type'] = 'coldel';
            $obj[0]['inventory']['columns'][$disc]['type'] = 'coldel';
            $obj[0]['inventory']['columns'][$ext]['type'] = 'coldel';
        }

        $obj[0]['inventory']['columns'][$ref]['lookupclass'] = 'refpo';
        if (!$access['changeamt']) {
            $obj[0]['inventory']['columns'][$rrcost]['readonly'] = true;
            $obj[0]['inventory']['columns'][$disc]['readonly'] = true;
        }
        $obj[0]['inventory']['descriptionrow'] = [];
        $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $companyid = $config['params']['companyid'];
        $isversion = $this->companysetup->getiscreateversion($config['params']);
        $pr_access = $this->othersClass->checkAccess($config['params']['user'], 2548);
        $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368);
        $canvass_access = $this->othersClass->checkAccess($config['params']['user'], 4192);
        $prmod = $this->othersClass->checkAccess($config['params']['user'], 618);

        $ispr =  $this->companysetup->getispr($config['params']);

        $tbuttons = [];
        if ($ispr) {
            array_push($tbuttons, 'pendingpr');
        }

        array_push($tbuttons, 'additem', 'quickadd', 'saveitem', 'deleteallitem');

        if ($isversion) {
            array_push($tbuttons, 'pendingso');
            array_push($tbuttons, 'additem', 'quickadd', 'saveitem', 'deleteallitem');
        }
        if ($ispr) {
            if ($prmod == 0) {
                unset($tbuttons[0]);
            } else {
                if ($pr_access == 0) {
                    unset($tbuttons[0]);
                }
            }
        }
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function createHeadField($config)
    {
        $systype = $this->companysetup->getsystemtype($config['params']);
        $noeditdate = $this->othersClass->checkAccess($config['params']['user'], 4851);
        $fields = ['docno', 'client', 'clientname', 'address'];

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.label', 'Vendor');
        data_set($col1, 'docno.label', 'Transaction#');

        $fields = [['dateid', 'terms'], 'due', 'whname'];


        $col2 = $this->fieldClass->create($fields);

        data_set($col2, 'whname.required', true);
        data_set($col2, 'whname.type', 'lookup');
        data_set($col2, 'whname.action', 'lookupclient');
        data_set($col2, 'whname.lookupclass', 'wh');

        $fields = [['yourref', 'ourref'], ['cur', 'forex']];

        $col3 = $this->fieldClass->create($fields);

        $fields = ['rem'];
        $col4 = $this->fieldClass->create($fields);
        return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
    }
    public function defaultheaddata($params)
    {
        $data = [];
        $data[0]['trno'] = 0;
        $data[0]['docno'] = '';
        $data[0]['dateid'] = date('Y-m-d');
        $data[0]['due'] = date('Y-m-d');
        $data[0]['client'] = 'CL0000000000001';
        $data[0]['clientname'] = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['client']]);
        $data[0]['address'] = $this->coreFunctions->getfieldvalue('client', 'addr', 'client=?', [$data[0]['client']]);
        $data[0]['yourref'] = '';
        $data[0]['ourref'] = '';
        $data[0]['rem'] = '';
        $data[0]['terms'] = '';
        $data[0]['forex'] = 1;
        $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
        $data[0]['projectcode'] = '';
        $data[0]['projectname'] = '';
        $data[0]['projectid'] = 0;
        $data[0]['subprojectname'] = '';
        $data[0]['subproject'] = 0;
        $data[0]['dwhname'] = '';
        $data[0]['dbranchname'] = '';
        $data[0]['branch'] = 0;
        $data[0]['branchcode'] = '';
        $data[0]['ddeptname'] = '';
        $data[0]['deptid'] = '0';
        $data[0]['dept'] = '';

        $data[0]['tax'] = 0;
        $data[0]['vattype'] = 'NON-VATABLE';


        $data[0]['wh'] = $this->companysetup->getwh($params);
        $name = $this->coreFunctions->datareader("select clientname as value from client where client='" . $data[0]['wh'] . "'");
        $data[0]['whname'] = $name;
        $data[0]['empid'] = '0';
        $data[0]['empname'] = '';
        $data[0]['empcode'] = '';
        $data[0]['tel2'] = '';
        $data[0]['dvattype'] = '';
        $data[0]['sotrno'] = '0';
        $data[0]['sodocno'] = '';
        $data[0]['billid'] = '0';
        $data[0]['shipid'] = '0';
        $data[0]['shipcontactid'] = '0';
        $data[0]['billcontactid'] = '0';
        $data[0]['revision'] = '';
        $data[0]['rqtrno'] = '0';
        $data[0]['deldate'] = date('Y-m-d');
        $data[0]['deladdress'] = '';
        $data[0]['whreceiver'] = '0';
        $data[0]['whreceivername'] = '';
        $data[0]['insurance'] = 0;
        $data[0]['ewt'] = '';
        $data[0]['ewtrate'] = '0';

        $data[0]['dprojectname'] = '';
        $data[0]['phaseid'] = 0;
        $data[0]['phase'] = '';
        $data[0]['modelid'] = 0;
        $data[0]['housemodel'] = '';
        $data[0]['blklotid'] = 0;
        $data[0]['blklot'] = '';
        $data[0]['lot'] = '';
        $data[0]['amenityid'] = 0;
        $data[0]['amenityname'] = '';
        $data[0]['subamenityid'] = 0;
        $data[0]['subamenityname'] = '';

        return $data;
    }

    public function createnewtransaction($docno, $params)
    {
        $companyid = $params['companyid'];
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
        $data[0]['terms'] = '';

        $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
        $data[0]['wh'] = $this->companysetup->getwh($params);
        $name = $this->coreFunctions->datareader("select clientname as value from client where client='" . $data[0]['wh'] . "'");
        $data[0]['whname'] = $name;
        $data[0]['forex'] = 1;

        $data[0]['projectcode'] = '';
        $data[0]['projectname'] = '';
        $data[0]['projectid'] = 0;
        $data[0]['subprojectname'] = '';
        $data[0]['subproject'] = 0;
        $data[0]['dwhname'] = '';
        $data[0]['dbranchname'] = '';
        $data[0]['branch'] = 0;
        $data[0]['branchcode'] = '';
        $data[0]['ddeptname'] = '';
        $data[0]['deptid'] = '0';
        $data[0]['dept'] = '';

        $data[0]['tax'] = 0;
        $data[0]['vattype'] = 'NON-VATABLE';

        $data[0]['empid'] = '0';
        $data[0]['empname'] = '';
        $data[0]['empcode'] = '';
        $data[0]['tel2'] = '';
        $data[0]['dvattype'] = '';
        $data[0]['sotrno'] = '0';
        $data[0]['sodocno'] = '';
        $data[0]['billid'] = '0';
        $data[0]['shipid'] = '0';
        $data[0]['shipcontactid'] = '0';
        $data[0]['billcontactid'] = '0';
        $data[0]['revision'] = '';
        $data[0]['rqtrno'] = '0';
        $data[0]['deldate'] = $this->othersClass->getCurrentDate();
        $data[0]['deladdress'] = '';
        $data[0]['whreceiver'] = '0';
        $data[0]['whreceivername'] = '';
        $data[0]['insurance'] = 0;
        $data[0]['ewt'] = '';
        $data[0]['ewtrate'] = '0';
        $data[0]['instructions'] = '';
        $data[0]['declaredval'] = '0';

        $data[0]['dprojectname'] = '';
        $data[0]['phaseid'] = 0;
        $data[0]['phase'] = '';
        $data[0]['modelid'] = 0;
        $data[0]['housemodel'] = '';
        $data[0]['blklotid'] = 0;
        $data[0]['blklot'] = '';
        $data[0]['lot'] = '';
        $data[0]['amenityid'] = 0;
        $data[0]['amenityname'] = '';
        $data[0]['subamenityid'] = 0;
        $data[0]['subamenityname'] = '';
        $data[0]['expiration'] = $this->othersClass->getCurrentDate();
        $data[0]['expiryid'] = 0;
        $data[0]['days'] = 0;

        $data[0]['isfa'] = '0';
        return $data;
    }

    public function loadheaddata($config)
    {
        $doc = $config['params']['doc'];
        $center = $config['params']['center'];
        $companyid = $config['params']['companyid'];
        $trno = $config['params']['trno'];
        $tablenum = $this->tablenum;
        $user = $config['params']['user'];
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


        $addedfield = ", head.yourref, head.rqtrno, left(head.deldate, 10) as deldate, head.deladdress";
        $addedjoin = "";

        $qryselect = "select
         num.center,
         head.trno,
         head.docno,
         client.client,
         head.terms,
         head.cur,
         head.forex,
         head.ourref,
         left(head.dateid,10) as dateid,
         head.clientname,
         head.address,
         head.shipto,
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         head.tax,
         head.billid,
         head.shipid,
         head.billcontactid,
         head.shipcontactid,
         head.vattype,
         head.isfa,
         '' as dvattype,
         head.agent,
         agent.clientname as agentname,
         head.wh as wh,
         warehouse.clientname as whname,
         '' as dwhname,
          head.phaseid,
          head.modelid,
          head.blklotid,
          head.amenityid,
          head.subamenityid,
         left(head.due,10) as due,
         hinfo.instructions,hinfo.declaredval,
         client.groupid,head.projectid,ifnull(p.code,'') as projectcode,ifnull(p.name,'') as projectname,
         s.line as subproject,s.subproject as subprojectname,head.branch,ifnull(b.clientname,'') as branchname,
         ifnull(b.client,'') as branchcode,'' as dbranchname,ifnull(d.client,'') as dept,
         ifnull(d.clientname,'') as deptname,head.deptid,'' as ddeptname, head.empid, e.clientname as empname,
         e.client as empcode, e.tel2,head.sotrno,ifnull(so.docno,'') as sodocno,
         head.revision, head.whreceiver,num.statid, ifnull(whreceiver.clientname, '') as whreceivername,head.insurance, 
         head.ewtrate,head.ewt,'' as dewt,hinfo.instructions,hinfo.declaredval,head.ewt,head.ewtrate
         " . $addedfield . "  ";

        $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join client as b on b.clientid = head.branch
        left join client as d on d.clientid = head.deptid
        left join client as e on e.clientid = head.empid
        left join projectmasterfile as p on p.line = head.projectid
        left join subproject as s on s.line = head.subproject
        left join hsqhead as so on so.trno = head.sotrno
        left join client as whreceiver on whreceiver.clientid = head.whreceiver
        left join " . $this->infohead . " as hinfo on hinfo.trno=head.trno
        " . $addedjoin . "
        where head.trno = ? and head.doc = ? and num.center = ?
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join client as b on b.clientid = head.branch
        left join client as d on d.clientid = head.deptid
        left join client as e on e.clientid = head.empid
        left join projectmasterfile as p on p.line = head.projectid
        left join subproject as s on s.line = head.subproject
        left join hsqhead as so on so.trno = head.sotrno
        left join client as whreceiver on whreceiver.clientid = head.whreceiver
        left join " . $this->hinfohead . " as hinfo on hinfo.trno=head.trno
        " . $addedjoin . "
        where head.trno = ? and head.doc = ? and num.center=? ";
        $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
        if (!empty($head)) {

            foreach ($this->blnfields as $key => $value) {
                if ($head[0]->$value) {
                    $head[0]->$value = "1";
                } else
                    $head[0]->$value = "0";
            }

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
            return  [
                'head' => $head,
                'griddata' => ['inventory' => $stock],
                'islocked' => $islocked,
                'isposted' => $isposted,
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
            // if (isset($head[$key]) || is_null($head[$key]))
            if (array_key_exists($key, $head)) {
                $data[$key] = $head[$key];
                if (!in_array($key, $this->except)) {
                    $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], '', $companyid);
                } //end if
            }
        }

        if ($data['terms'] == '') {
            $data['due'] =  $data['dateid'];
        } else {
            $data['due'] = $this->othersClass->computeterms($data['dateid'], $data['dateid'], $data['terms']);
        }

        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];

        $info = [];

        $info['instructions'] = $head['instructions'];
        $info['declaredval'] = $head['declaredval'];


        if ($isupdate) {
            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
            $this->recomputecost($head, $config);
        } else {
            $data['doc'] = $config['params']['doc'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $insert = $this->coreFunctions->sbcinsert($this->head, $data);
            $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
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
        $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->infohead . " where trno=?", 'delete', [$trno]);
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
      terms,rem,forex,yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,agent,wh,due,cur,projectid,subproject,branch,deptid,sotrno,billid,shipid,vattype,tax,empid,billcontactid,shipcontactid,
      revision, rqtrno, deldate, deladdress, whreceiver,insurance,ewtrate,ewt,
      phaseid,modelid,blklotid,amenityid,subamenityid,expiration,isfa,expiryid)
      SELECT head.trno,head.doc, head.docno,head.client, head.clientname, head.address,head.shipto,
      head.dateid as dateid, head.terms, head.rem, head.forex,head.yourref, head.ourref,
      head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,head.agent,head.wh,
      head.due,head.cur,head.projectid,head.subproject,head.branch,head.deptid,head.sotrno,head.billid,head.shipid,
      head.vattype,head.tax,head.empid,head.billcontactid,head.shipcontactid,
      head.revision, head.rqtrno, head.deldate, head.deladdress, head.whreceiver,head.insurance,head.ewtrate,head.ewt,
      head.phaseid,head.modelid,head.blklotid,head.amenityid,head.subamenityid,head.expiration,head.isfa,head.expiryid
      FROM " . $this->head . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno
      where head.trno=? limit 1";
        $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
        if ($posthead) {
            // for glstock

            if (!$this->othersClass->postingstockinfotrans($config)) {
                $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
                return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while posting stock/s.'];
            }

            $qry = "insert into " . $this->hstock . "(trno,line,itemid,uom,
        whid,loc,ref,disc,cost,qty,void,rrcost,rrqty,ext,
        encodeddate,qa,encodedby,editdate,editby,sku,refx,linex,cdrefx,cdlinex,rem,stageid, projectid,sorefx,solinex,osrefx,oslinex,sgdrate,poref,sortline,phaseid,modelid,blklotid,amenityid,subamenityid,sjrefx,sjlinex)
        SELECT trno, line, itemid, uom,whid,loc,ref,disc,cost, qty,void,rrcost, rrqty, ext,
        encodeddate,qa, encodedby,editdate,editby,sku,refx,linex,cdrefx,cdlinex,rem,stageid, projectid ,sorefx,solinex,osrefx,oslinex,sgdrate,poref,sortline,
        phaseid,modelid,blklotid,amenityid,subamenityid,sjrefx,sjlinex
        FROM " . $this->stock . " where trno =?";
            if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
                //update transnum
                $date = $this->othersClass->getCurrentTimeStamp();
                $data = ['postdate' => $date, 'postedby' => $config['params']['user'], 'statid' => 5];
                $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
                $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from stockinfotrans where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from " . $this->infohead . " where trno=?", 'delete', [$trno]);
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
    yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,wh,due,cur,projectid,subproject,branch,deptid,sotrno,billid,shipid,vattype,tax,empid,billcontactid,shipcontactid,
    revision, rqtrno, deldate, deladdress, whreceiver,insurance,ewtrate,ewt,
      phaseid,modelid,blklotid,amenityid,subamenityid,expiration,isfa,expiryid)
    select head.trno, head.doc, head.docno, client.client, head.clientname, head.address, head.shipto,
    head.dateid as dateid, head.terms, head.rem, head.forex, head.yourref, head.ourref, head.createdate,
    head.createby, head.editby, head.editdate, head.lockdate, head.lockuser,head.wh,head.due,head.cur,head.projectid,head.subproject,head.branch,head.deptid,head.sotrno,head.billid,head.shipid,head.vattype,head.tax,head.empid,head.billcontactid,head.shipcontactid,
    head.revision, head.rqtrno, head.deldate, head.deladdress, head.whreceiver,head.insurance,head.ewtrate,head.ewt,
      head.phaseid,head.modelid,head.blklotid,head.amenityid,head.subamenityid,head.expiration,head.isfa,head.expiryid
    from (" . $this->hhead . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno)left join client on client.client=head.client
    where head.trno=? limit 1";
        //head
        if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
            if (!$this->othersClass->unpostingstockinfotrans($config)) {
                $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
                return ['trno' => $trno, 'status' => false, 'msg' => 'Unposting failed. There are issues with inventory.'];
            }

            $qry = "insert into " . $this->stock . "(
      trno,line,itemid,uom,whid,loc,ref,disc,
      cost,qty,void,rrcost,rrqty,ext,rem,encodeddate,qa,encodedby,editdate,editby,sku,refx,linex,cdrefx,cdlinex,stageid, projectid,sorefx,solinex,osrefx,oslinex,sgdrate,poref,sortline,
      phaseid,modelid,blklotid,amenityid,subamenityid,sjrefx,sjlinex)
      select trno, line, itemid, uom,whid,loc,ref,disc,cost, qty,void, rrcost, rrqty,
      ext,rem, encodeddate, qa, encodedby, editdate, editby,sku,refx,linex,cdrefx,cdlinex,stageid, projectid,sorefx,solinex,osrefx,oslinex,sgdrate,poref,sortline,
      phaseid,modelid,blklotid,amenityid,subamenityid,sjrefx,sjlinex
      from " . $this->hstock . " where trno=?";
            //stock
            if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
                $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null,statid = 0 where trno=?", 'update', [$trno]);
                $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from hstockinfotrans where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from " . $this->hinfohead . " where trno=?", 'delete', [$trno]);
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

        $sqlselect = "select item.brand as brand,
    ifnull(mm.model_name,'') as model,
    item.itemid,
    stock.trno,
    stock.line,
    stock.sortline,
    stock.refx,
    stock.linex,
    stock.cdrefx,
    stock.cdlinex,
    stock.sorefx,
    stock.solinex,
    item.barcode,
    if(ifnull(sit.itemdesc,'')='',item.itemname,sit.itemdesc) as itemname,
    stock.uom,
    stock.cost,
    stock.qty as qty,
    FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,
    stock.rrcost as rrcost2,
    FORMAT(stock.rrqty," . $qty_dec . ")  as rrqty,
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
    stock.rem, stock.stageid,st.stage,
    ifnull(uom.factor,1) as uomfactor,
    FORMAT(stock.qa," . $this->companysetup->getdecimal('currency', $config['params']) . ") as served, 
    '' as bgcolor,
    case when stock.void=0 then '' else 'bg-red-2' end as errcolor,(case  when " . $companyid . " in (10,12) then (case when stock.qa<>stock.qty and stock.void <>1 then 'bg-orange-2' else '' end) else '' end) as qacolor,
    prj.name as stock_projectname,
    item.subcode, item.partno, round(item.dqty, " . $this->companysetup->getdecimal('qty', $config['params']) . ") as boxcount,stock.osrefx,stock.oslinex,stock.sgdrate,stock.poref,
    concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',ifnull(i.itemdescription,'')) as itemdescription,
    
    stock.projectid, proj.code as project,
    stock.phaseid, ph.code as phasename,
    stock.modelid, hm.model as housemodel, 
    stock.blklotid, bl.blk, bl.lot,
    am.line as amenity,
    am.description as amenityname,
    subam.line as subamenity,
    subam.description as subamenityname,
    FORMAT(sit.prevamt," . $this->companysetup->getdecimal('price', $config['params']) . ") as prevamt,
    date(sit.prevdate) as prevdate, FORMAT(ifnull(sit.amt1,0)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt1,stock.sjrefx,stock.sjlinex    
    ";
        return $sqlselect;
    }

    public function openstock($trno, $config)
    {
        $sqlselect = $this->getstockselect($config);

        $qry = $sqlselect . "
    FROM $this->stock as stock
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid
    left join stagesmasterfile as st on st.line = stock.stageid
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join model_masterfile as mm on mm.model_id = item.model
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
    left join stockinfotrans as sit on sit.trno = stock.trno and sit.line=stock.line

    left join projectmasterfile as proj on proj.line = stock.projectid
    left join phase as ph on ph.line = stock.phaseid
    left join housemodel as hm on hm.line = stock.modelid
    left join blklot as bl on bl.line = stock.blklotid
    left join amenities as am on am.line= stock.amenityid
    left join subamenities as subam on subam.line=stock.subamenityid and subam.amenityid=stock.amenityid

    where stock.trno =?
    UNION ALL
    " . $sqlselect . "
    FROM $this->hstock as stock
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join client as warehouse on warehouse.clientid=stock.whid
    left join stagesmasterfile as st on st.line = stock.stageid
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join model_masterfile as mm on mm.model_id = item.model
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
    left join hstockinfotrans as sit on sit.trno = stock.trno and sit.line=stock.line
    
    left join projectmasterfile as proj on proj.line = stock.projectid
    left join phase as ph on ph.line = stock.phaseid
    left join housemodel as hm on hm.line = stock.modelid
    left join blklot as bl on bl.line = stock.blklotid
    left join amenities as am on am.line= stock.amenityid
    left join subamenities as subam on subam.line=stock.subamenityid and subam.amenityid=stock.amenityid

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
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid
    left join stagesmasterfile as st on st.line = stock.stageid
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join model_masterfile as mm on mm.model_id = item.model
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
    left join stockinfotrans as sit on sit.trno = stock.trno and sit.line=stock.line
    
    left join projectmasterfile as proj on proj.line = stock.projectid
    left join phase as ph on ph.line = stock.phaseid
    left join housemodel as hm on hm.line = stock.modelid
    left join blklot as bl on bl.line = stock.blklotid
    left join amenities as am on am.line= stock.amenityid
    left join subamenities as subam on subam.line=stock.subamenityid and subam.amenityid=stock.amenityid

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
            case 'getcdsummary':
                return $this->getcdsummary($config);
                break;
            case 'getcddetails':
                return $this->getcddetails($config);
                break;
            case 'getprsummary':
                return $this->getprsummary($config);
                break;
            case 'getprdetails':
                return $this->getprdetails($config);
                break;
            case 'getsosummary':
                return $this->getsosummary($config);
                break;
            case 'getsodetails':
                return $this->getsodetails($config);
                break;
            case 'getsqsummary':
                return $this->getsqposummary($config);
                break;
            case 'getsqdetails':
                return $this->getsqdetails($config);
                break;
            case 'getcriticalstocks':
                return $this->getcriticalstocks($config);
                break;
            case 'getossummary':
                return $this->getossummary($config);
                break;
            case 'getosdetails':
                return $this->getosdetails($config);
                break;
            case 'getsjsummary':
                return $this->getsjsummary($config);
                break;
            case 'getsjdetails':
                return $this->getsjdetails($config);
                break;
            case 'getitem':
                return $this->othersClass->getmultiitem($config);
                break;
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
                break;
        }
    }


    public function stockstatusposted($config)
    {
        switch ($config['params']['action']) {
            case 'updateitemvoid':
                return $this->updateitemvoid($config);
                break;
            case 'diagram':
                return $this->diagram($config);
                break;
            case 'exportcsv':
                return $this->exportcsv($config, 'F');
                break;
            case 'exportcsvd':
                return $this->exportcsv($config, 'D');
                break;
            case 'exportcdocsv':
                return $this->exportcdocsv($config);
                break;
            case 'jumpmodule':
                return ['status' => true, 'action' => 'loaddocument', 'msg' => 'Open SO', 'doc' => 'SQ', 'trno' => $config['params']['addedparams'][0], 'docno' => $config['params']['addedparams'][1], 'moduletype' => 'module', 'url' => '/module/sales/'];
                break;
            case 'print1':
                return $this->reportsetup($config);
                break;
            case 'navigation':
                return $this->othersClass->navigatedocno($config);
                break;
            case 'donetodo':
                $tablenum = $this->tablenum;
                return $this->othersClass->donetodo($config, $tablenum);
                break;
            case 'forapproval':
                $tablenum = $this->tablenum;
                return $this->othersClass->forapproval($config, $tablenum);
                break;
            case 'doneapproved':
                $tablenum = $this->tablenum;
                return $this->othersClass->approvedsetup($config, $tablenum);
                break;
            case 'uploadexcel':
                return $this->uploadexcel($config);
                break;
            case 'downloadexcel':
                return $this->othersClass->downloadexcel($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
                break;
        }
    }

    public function uploadexcel($config)
    {
        $rawdata = $config['params']['data'];
        $companyid = $config['params']['companyid'];
        $trno = $config['params']['dataparams']['trno'];
        $msg = '';
        $status = true;
        $uniquefield = "itemcode";
        $loc = '';

        if ($trno == 0) {
            return ['trno' => $trno, 'status' => false, 'msg' => 'Kindly create the document number first.'];
        }

        foreach ($rawdata as $key => $value) {
            try {
                $itemid = $this->coreFunctions->getfieldvalue("item", "itemid", "barcode = '" . $rawdata[$key][$uniquefield] . "'");

                if ($itemid == '') {
                    $status = false;
                    $msg .= 'Failed to upload. ' . $rawdata[$key][$uniquefield] . ' does not exist. ';
                    continue;
                }

                $uom_exist = $this->coreFunctions->getfieldvalue("uom", "uom", "itemid = " . $itemid . " and uom = '" . $rawdata[$key]['uom'] . "'");
                if ($uom_exist == '') {
                    $msg .= 'Failed to upload ' . $rawdata[$key][$uniquefield] . ' uom does not exist. ';
                    continue;
                }

                if (isset($rawdata[$key]['loc'])) {
                }

                $config['params']['trno'] = $trno;
                $config['params']['data']['uom'] = $rawdata[$key]['uom'];
                $config['params']['data']['itemid'] = $itemid;
                $config['params']['data']['qty'] = $rawdata[$key]['qty'];
                $config['params']['data']['wh'] =  $this->coreFunctions->getfieldvalue($this->head, "wh", "trno = ?", [$trno]);
                $config['params']['data']['amt'] = isset($rawdata[$key]['cost']) ? $rawdata[$key]['cost'] : $rawdata[$key]['amt'];
                $config['params']['data']['disc'] = isset($rawdata[$key]['disc']) ? $rawdata[$key]['disc'] : "";

                $return = $this->additem('insert', $config);
                if (!$return['status']) {
                    $status = false;
                    $msg .= 'Failed to upload. ' . $return['msg'];
                    goto exithere;
                }
            } catch (Exception $e) {
                $status = false;
                $msg .= 'Failed to upload. Exception error ' . $e->getMessage();
                goto exithere;
            }
        }

        exithere:
        if ($msg == '') {
            $this->logger->sbcwritelog($trno, $config, 'IMPORT', 'UPLOAD EXCEL FILE');
            $msg = 'Successfully uploaded.';
        }

        if (!$status) {
            $this->coreFunctions->execqry("delete from lastock where trno=" . $trno);
        }

        return ['trno' => $trno, 'status' => $status, 'msg' => $msg, 'reloadhead' => true];
    }

    public function exportcsv($config, $type)
    {
        $trno = $config['params']['trno'];
        $str = "";
        $separator = "@@@";
        $nextline = "###";
        $filename = '';

        switch ($type) {
            case 'F':
                $qry = "select head.trno as exportid, head.yourref as ponum,concat(right(num.bref,1),right(num.yr,2),right(head.docno,5)) as erp,ifnull(date_format(qtn.dateid,'%m%/%d%/%Y'),'') as qtndate,
        ifnull(qtn.clientname,'') as customername, ifnull(concat(conbill.fname,' ', conbill.mname, ' ',conbill.lname),'') as contactperson,
        ifnull(qtn.terms, '') as terms, 'Exworks' as fob,
        0 as zero1, 0 as zero2,ifnull(date_format(so.dateid,'%m%/%d%/%Y'),'') as custpodate, ifnull(date_format(qtn.deldate,'%m%/%d%/%Y'),'') as deliverydate,
        date_format(head.dateid,'%m%/%d%/%Y') as podate,
        'URGENT' as priority,ifnull(concat(conbill.fname, ' ', conbill.mname, ' ',conbill.lname),'') as billname,
        ifnull(bill.addrline1,'') as billaddr1,ifnull(bill.addrline2,'') as billaddr2,ifnull(bill.city,'') as billcity,
        ifnull(bill.country,'') as billcountry,ifnull(bill.contactno,'') as billcontactno,ifnull(bill.fax,'') as billfax,
        ifnull(conbill.email,'') as billemail,
        ifnull(concat(conship.fname, ' ', conship.mname, ' ',conship.lname),'') as shipname,
        ifnull(ship.addrline1,'') as shipaddr1,ifnull(ship.addrline2,'') as shipaddr2,ifnull(ship.city,'') as shipcity,
        ifnull(ship.country,'') as shipcountry,ifnull(ship.contactno,'') as shipcontactno,ifnull(ship.fax,'') as shipfax,
        ifnull(conship.email,'') as shipemail,head.rem,date_format(curdate(),'%m%/%d%/%Y') as dategenerated
        from pohead as head
        left join hsqhead as so on so.trno=head.sotrno
        left join hqshead as qtn on qtn.sotrno=so.trno
        left join client on client.client=qtn.client
        left join contactperson as conbill on conbill.line=qtn.billcontactid
        left join contactperson as conship on conship.line=qtn.shipcontactid
        left join billingaddr as bill on bill.line = qtn.billid and bill.clientid = client.clientid
        left join billingaddr as ship on ship.line = qtn.shipid and ship.clientid = client.clientid
        left join transnum as num on num.trno=head.trno
        where head.doc='po' and head.trno=" . $trno . "
        union all
        select head.trno as exportid, head.yourref as ponum,concat(right(num.bref,1),right(num.yr,2),right(head.docno,5)) as erp,ifnull(date_format(qtn.dateid,'%m%/%d%/%Y'),'') as qtndate,
        ifnull(qtn.clientname,'') as customername, ifnull(concat(conbill.fname, ' ', conbill.mname, ' ',conbill.lname),'') as contactperson,
        ifnull(qtn.terms, '') as terms, '???' as fob,
        0 as zero1, 0 as zero2,ifnull(date_format(so.dateid,'%m%/%d%/%Y'),'') as custpodate, ifnull(date_format(qtn.deldate,'%m%/%d%/%Y'),'') as deliverydate,
        date_format(head.dateid,'%m%/%d%/%Y') as podate,
        'URGENT' as priority,ifnull(concat(conbill.fname, ' ', conbill.mname, ' ',conbill.lname),'') as billname,
        ifnull(bill.addrline1,'') as billaddr1,ifnull(bill.addrline2,'') as billaddr2,ifnull(bill.city,'') as billcity,
        ifnull(bill.country,'') as billcountry,ifnull(bill.contactno,'') as billcontactno,ifnull(bill.fax,'') as billfax,
        ifnull(conbill.email,'') as billemail,
        ifnull(concat(conship.fname, ' ', conship.mname, ' ',conship.lname),'') as shipname,
        ifnull(ship.addrline1,'') as shipaddr1,ifnull(ship.addrline2,'') as shipaddr2,ifnull(ship.city,'') as shipcity,
        ifnull(ship.country,'') as shipcountry,ifnull(ship.contactno,'') as shipcontactno,ifnull(ship.fax,'') as shipfax,
        ifnull(conship.email,'') as shipemail,head.rem,date_format(curdate(),'%m%/%d%/%Y') as dategenerated
        from hpohead as head
        left join hsqhead as so on so.trno=head.sotrno
        left join hqshead as qtn on qtn.sotrno=so.trno
        left join client on client.client=qtn.client
        left join contactperson as conbill on conbill.line=qtn.billcontactid
        left join contactperson as conship on conship.line=qtn.shipcontactid
        left join billingaddr as bill on bill.line = qtn.billid and bill.clientid = client.clientid
        left join billingaddr as ship on ship.line = qtn.shipid and ship.clientid = client.clientid
         left join transnum as num on num.trno=head.trno
        where head.doc='po' and head.trno=" . $trno;

                $data = $this->coreFunctions->opentable($qry);
                if (!empty($data)) {

                    if ($data[0]->rem == "") {
                        $rem = ' ';
                    } else {
                        $rem = $data[0]->rem;
                    }

                    $str = $data[0]->erp . $separator . $data[0]->ponum . $separator . $data[0]->erp . $separator . $data[0]->podate . $separator . $data[0]->customername . $separator . $data[0]->contactperson;
                    $str = $str . $separator . $data[0]->terms . $separator . $data[0]->fob . $separator . $data[0]->fob . $separator . '0' . $separator . $data[0]->podate . $separator . $data[0]->podate . $separator . $data[0]->deliverydate . $separator . $data[0]->dategenerated . $separator . $data[0]->priority;
                    $str = $str . $separator . $data[0]->customername . $separator . $data[0]->billaddr1 . ' ' . $data[0]->billaddr2 . ' City: ' . $data[0]->billcity . ' Country: ' . $data[0]->billcountry . ' Phone: ' . $data[0]->billcontactno . "\t" . 'Fax: ' . $data[0]->billfax . ' Email: ' . $data[0]->billemail;
                    $str = $str . $separator . $data[0]->shipaddr1 . ' ' . $data[0]->shipaddr2 . ' City: ' . $data[0]->shipcity . ' Country: ' . $data[0]->shipcountry . ' Phone: ' . $data[0]->shipcontactno . "\t" . 'Fax: ' . $data[0]->shipfax . ' Email: ' . $data[0]->shipemail;
                    $str = $str . $separator . $data[0]->billname . $separator . $data[0]->shipaddr1 . ' ' . $data[0]->shipaddr2 . ' City: ' . $data[0]->shipcity . ' Country: ' . $data[0]->shipcountry . ' Phone: ' . $data[0]->shipcontactno . "\t" . 'Fax: ' . $data[0]->shipfax . ' Email: ' . $data[0]->shipemail . $separator . $data[0]->shipname . $separator . $rem;
                    $str = $str . $separator . "0" . $separator . $data[0]->erp . "####";
                    $filename = 'POCPHP-F-' . $data[0]->erp . date("dmyHi");
                }
                break;

            case 'D':
                $qry = "select concat(right(num.bref,1),right(num.yr,2),right(head.docno,5)) as erp, stock.line, head.yourref as ponum, stock.rrqty as pocustqty, stock.uom,
        head.cur as currency, round(stock.rrcost,4) as poprice, stock.rrqty as pobalance,
        date_format(head.dateid,'%m%/%d%/%Y') as podate,date_format(qtn.deldate,'%m%/%d%/%Y') as deliverydate,date_format(curdate(),'%m%/%d%/%Y') as dategenerated,brand.brand_desc,
        item.itemname, model.model_name,(case when item.itemname = model.model_name then 
        concat(model.model_name,' ',p.name,' ',brand.brand_desc,' ',iteminfo.itemdescription) else concat(item.itemname,' ',model.model_name,' ',p.name,' ',brand.brand_desc,' ',iteminfo.itemdescription) end) as itemdescription,iteminfo.accessories,stockinfo.rem as itemremarks,
        brand.brand_desc as crossmfr, model.model_name as crossmfritemno,
        0 as weight, 0 as ssm1, 0 as price1, 0 as ssm2, 0 as price2, 0 as ssm3, 0 as price3, 0 as ssm4, 0 as price4,
        0 as ssm5, 0 as price5, 0 as ssm6, 0 as price6,p.name as project
        from pohead as head
        left join postock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join stockgrp_masterfile as grp on grp.stockgrp_id = item.groupid
        left join part_masterfile as part on part.part_id = item.part
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join model_masterfile as model on model.model_id = item.model
        left join projectmasterfile as p on p.line = item.projectid
        left join iteminfo on iteminfo.itemid = item.itemid
        left join stockinfotrans as stockinfo on stockinfo.trno = head.trno and stockinfo.line = stock.line
        left join hsqhead as so on so.trno=head.sotrno
        left join hqshead as qtn on qtn.sotrno=so.trno
          left join transnum as num on num.trno=head.trno
        where head.doc='po' and head.trno=" . $trno . "  and (stock.void<>1 or stock.qa<>0)
        union all
        select concat(right(num.bref,1),right(num.yr,2),right(head.docno,5)) as erp, stock.line, head.yourref as ponum, stock.rrqty as pocustqty, stock.uom,
        head.cur as currency, round(stock.rrcost,4) as poprice, stock.rrqty as pobalance,
        date_format(head.dateid,'%m%/%d%/%Y') as podate,date_format(qtn.deldate,'%m%/%d%/%Y') as deliverydate,date_format(curdate(),'%m%/%d%/%Y') as dategenerated,brand.brand_desc,
        item.itemname, model.model_name,(case when item.itemname = model.model_name then 
        concat(model.model_name,' ',p.name,' ',brand.brand_desc,' ',iteminfo.itemdescription) else concat(item.itemname,' ',model.model_name,' ',p.name,' ',brand.brand_desc,' ',iteminfo.itemdescription) end)as itemdescription,iteminfo.accessories,stockinfo.rem as itemremarks,
        brand.brand_desc as crossmfr, model.model_name as crossmfritemno,
        0 as weight, 0 as ssm1, 0 as price1, 0 as ssm2, 0 as price2, 0 as ssm3, 0 as price3, 0 as ssm4, 0 as price4,
        0 as ssm5, 0 as price5, 0 as ssm6, 0 as price6,p.name as project
        from hpohead as head
        left join hpostock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join stockgrp_masterfile as grp on grp.stockgrp_id = item.groupid
        left join part_masterfile as part on part.part_id = item.part
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join model_masterfile as model on model.model_id = item.model
        left join projectmasterfile as p on p.line = item.projectid
        left join iteminfo on iteminfo.itemid = item.itemid
        left join stockinfotrans as stockinfo on stockinfo.trno = head.trno and stockinfo.line = stock.line
        left join hsqhead as so on so.trno=head.sotrno
        left join hqshead as qtn on qtn.sotrno=so.trno
          left join transnum as num on num.trno=head.trno
        where head.doc='po' and head.trno=" . $trno . " and (stock.void<>1 or stock.qa<>0)";

                $data = $this->coreFunctions->opentable($qry);
                if (!empty($data)) {
                    foreach ($data as $key => $val) {
                        if ($data[0]->accessories == "") {
                            $accessories = ' ';
                        } else {
                            $accessories = $data[0]->accessories;
                        }

                        if ($data[0]->itemdescription == "") {
                            $itemdescription = ' ';
                        } else {
                            $itemdescription = $data[0]->itemdescription;
                        }

                        $str = $str . $data[$key]->erp . $separator . $data[$key]->line . $separator . $data[$key]->ponum . $separator . round($data[$key]->pocustqty, 0) . $separator . $data[$key]->uom . $separator . $data[$key]->currency;
                        $str = $str . $separator . $data[$key]->poprice . $separator . round($data[$key]->pobalance, 0) . $separator . $data[$key]->podate . $separator . $data[$key]->deliverydate . $separator . $data[$key]->dategenerated . $separator . $data[$key]->project . $separator . $data[$key]->itemname . $separator . $data[$key]->model_name;
                        $str = $str . $separator . $itemdescription . $separator . $accessories . $separator;
                        $str = $str . $separator . $separator . $separator . '0' . $separator . $data[$key]->currency . $separator . round($data[$key]->pocustqty, 0) . $separator . $data[$key]->poprice . $separator . '0' . $separator . '0' . $separator . '0' . $separator . '0' . $separator . '0' . $separator . '0' . $separator . '0' . $separator . '0' . $separator . '0' . $separator . '0' . $separator;
                        $str = $str . "####";
                    }

                    $filename = 'POCPHP-D-' . $data[0]->erp . date("dmyHi");
                }

                break;
        }


        return ['status' => true, 'msg' => 'Successfully exported.', 'filename' => $filename, 'ext' => 'txt', 'csv' => $str];
    }

    public function exportcdocsv($config)
    {
        $trno = $config['params']['trno'];
        $str = "";
        $partno = "";
        $nextline = "\n";
        $separator = ",";
        $filename = '';

        $qry = "select stock.rrqty, item.partno,head.docno,head.dateid from postock as stock left join pohead as head on head.trno = stock.trno left join item on item.itemid = stock.itemid where stock.trno = ?
        union all
        select stock.rrqty, item.partno,head.docno,head.dateid from hpostock as stock left join hpohead as head on head.trno = stock.trno left join item on item.itemid = stock.itemid where stock.trno = ?";

        $this->coreFunctions->LogConsole($qry);

        $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
        if (!empty($data)) {
            foreach ($data as $key => $val) {
                $partno = str_replace("-", "", $data[$key]->partno);

                $str = $str . $partno . $separator . number_format($data[$key]->rrqty, 2) . $nextline;
            }

            $filename = $data[0]->docno . "-" . date("MdY", strtotime($data[0]->dateid));
        }

        $this->coreFunctions->LogConsole($str);
        return ['status' => true, 'msg' => 'Successfully exported.', 'filename' => $filename, 'ext' => 'csv', 'csv' => $str];
    }
    public function diagram($config)
    {
        $companyid = $config['params']['companyid'];
        $data = [];
        $nodes = [];
        $links = [];
        $data['width'] = 1500;
        $startx = 100;

        $qry = "select po.trno,po.docno,left(po.dateid,10) as dateid,
       CAST(concat('Total PO Amt: ',round(sum(s.ext),2)) as CHAR) as rem,s.refx
       from hpohead as po
       left join hpostock as s on s.trno = po.trno
       where po.trno = ?
       group by po.trno,po.docno,po.dateid,s.refx
       union all
       select po.trno,po.docno,left(po.dateid,10) as dateid,
       CAST(concat('Total PO Amt: ',round(sum(s.ext),2)) as CHAR) as rem,s.refx
       from pohead as po
       left join postock as s on s.trno = po.trno
       where po.trno = ?
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
                        'color' => 'blue',
                        'details' => [$t[$key]->dateid]
                    ]
                );
                array_push($links, ['from' => $t[$key]->docno, 'to' => 'rr']);
                $a = $a + 100;

                if ($companyid == 6) { // mitsukoshi
                    // PL
                    $qry = "select head.docno,left(head.dateid,10) as dateid,
          CAST(concat('Total PL Amt: ',round(sum(s.ext),2)) as CHAR) as rem
          from hplhead as head 
          left join hplstock as s on s.trno = head.trno
          left join hpostock as postock on postock.trno = s.refx and postock.line = s.linex
          left join hpohead as pohead on pohead.trno = postock.trno
          where pohead.trno = ?
          group by head.docno,head.dateid";
                    $x = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
                    $poref = $t[$key]->docno;
                    if (!empty($x)) {
                        foreach ($x as $key2 => $value) {
                            data_set(
                                $nodes,
                                $x[$key2]->docno,
                                [
                                    'align' => 'left',
                                    'x' => 300,
                                    'y' => 250,
                                    'w' => 250,
                                    'h' => 80,
                                    'type' => $x[$key2]->docno,
                                    'label' => $x[$key2]->rem,
                                    'color' => 'yellow',
                                    'details' => [$x[$key2]->dateid]
                                ]
                            );
                            array_push($links, ['from' => $x[$key2]->docno, 'to' => $poref]);
                        }
                    }
                }

                if (floatval($t[$key]->refx) != 0) {
                    //pr
                    $qry = "select po.docno,left(po.dateid,10) as dateid,
            CAST(concat('Total PR Amt: ',round(sum(s.ext),2)) as CHAR) as rem
            from hprhead as po left join hprstock as s on s.trno = po.trno
            where po.trno = ?
            group by po.docno,po.dateid";
                    $x = $this->coreFunctions->opentable($qry, [$t[$key]->refx]);
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
                                    'color' => 'yellow',
                                    'details' => [$x[$key2]->dateid]
                                ]
                            );
                            array_push($links, ['from' => $x[$key2]->docno, 'to' => $poref]);
                            $a = $a + 100;
                        }
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
      where stock.refx=? and head.doc = 'RR'
      group by head.docno, head.dateid, head.trno, ap.bal
      union all
      select head.docno,
      date(head.dateid) as dateid,
      CAST(concat('Total RR Amt: ', round(sum(stock.ext),2), ' - ', 'Balance: ', round(sum(stock.ext),2)) as CHAR) as rem,
      head.trno
      from lahead as head
      left join lastock as stock on head.trno = stock.trno
      where stock.refx=? and head.doc = 'RR'
      group by head.docno, head.dateid, head.trno";
        $t = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno']]);
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
                    'color' => 'green',
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
        where detail.refx = ? and head.doc = 'AP'
        union all
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from lahead as head
        left join ladetail as detail on head.trno = detail.trno
        where detail.refx = ? and head.doc = 'AP'";
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
                                'color' => 'red',
                                'details' => [$apvdata[$key2]->dateid]
                            ]
                        );
                        array_push($links, ['from' => 'rr', 'to' => 'apv']);
                        $a = $a + 100;
                    }
                }

                //CV
                if (!empty($apvdata)) {
                    $apv_rr_links = "apv";
                    $apvtrno = $apvdata[0]->trno;
                } else {
                    $apvtrno = $rrtrno;
                    $apv_rr_links = "rr";
                }
                $cvqry = "
        select head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from glhead as head
        left join gldetail as detail on head.trno = detail.trno
        where detail.refx = ? and head.doc = 'CV'
        union all
        select head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from lahead as head
        left join ladetail as detail on head.trno = detail.trno
        where detail.refx = ? and head.doc = 'CV'";
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
                                'color' => 'red',
                                'details' => [$cvdata[$key2]->dateid]
                            ]
                        );
                        array_push($links, ['from' => $apv_rr_links, 'to' => $cvdata[$key2]->docno]);
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
        where stock.refx=? and head.doc = 'DM'
        group by head.docno, head.dateid
        union all
        select head.docno as docno,left(head.dateid,10) as dateid,
        CAST(concat('Total DM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join item on item.itemid=stock.itemid
        where stock.refx=? and head.doc = 'DM'
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
                                'color' => 'red',
                                'details' => [$dmdata[$key2]->dateid]
                            ]
                        );
                        array_push($links, ['from' => 'rr', 'to' => $dmdata[$key2]->docno]);
                        $a = $a + 100;
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
                    $msg2 = ' Qty Received is Greater than PR Qty ';
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
        $wh = $config['params']['wh'];
        $item = $this->coreFunctions->opentable("select item.itemid,0 as amt,item.disc,'' as loc,'" . $wh . "' as wh, 1 as qty, uom,famt from item where barcode=?", [$barcode]);

        $item = json_decode(json_encode($item), true);
        $defuom = '';
        if (!empty($item)) {
            $config['params']['barcode'] = $barcode;
            $lprice = $this->getlatestprice($config);
            $lprice = json_decode(json_encode($lprice), true);
            if (!empty($lprice['data'])) {
                $item[0]['amt'] = $lprice['data'][0]['amt'];
                $item[0]['disc'] = $lprice['data'][0]['disc'];
            }

            if ($this->companysetup->getisdefaultuominout($config['params'])) {
                $defuom = $this->coreFunctions->datareader("select ifnull(uom.uom,'') as value from item left join uom on uom.itemid=item.itemid and uom.isdefault2 = 1 where item.barcode=?", [$barcode]);
                if ($defuom != "") {
                    $item[0]['uom'] = $defuom;
                }
            }

            $config['params']['data'] = $item[0];
            return $this->additem('insert', $config);
        } else {
            return ['status' => false, 'msg' => 'Barcode not found.' . $barcodelength, ''];
        }
    }

    // insert and update item
    public function additem($action, $config)
    {
        $systype = $this->companysetup->getsystemtype($config['params']);
        $classname = __NAMESPACE__ . '\\po';
        $config['docmodule'] = new $classname;
        $companyid = $config['params']['companyid'];
        $isproject = $this->companysetup->getisproject($config['params']);
        $uom = $config['params']['data']['uom'];
        $itemid = $config['params']['data']['itemid'];
        $trno = $config['params']['trno'];
        $disc = $config['params']['data']['disc'];
        $wh = $config['params']['data']['wh'];
        $loc = '';
        $itemdesc = '';
        $ref = '';
        $void = 'false';
        if (isset($config['params']['data']['void'])) {
            $void = $config['params']['data']['void'];
        }
        if (isset($config['params']['data']['ref'])) {
            $ref = $config['params']['data']['ref'];
        }

        if (isset($config['params']['data']['loc'])) {
            $loc = $config['params']['data']['loc'];
        }

        $refx = 0;
        $linex = 0;
        $cdrefx = 0;
        $cdlinex = 0;
        $sjrefx = 0;
        $sjlinex = 0;
        $sorefx = 0;
        $solinex = 0;
        $osrefx = 0;
        $oslinex = 0;
        $rem = '';
        $stageid = 0;
        $projectid = 0;
        $poref = '';
        $sgdrate = 0;
        $ext = 0;


        $phaseid = 0;
        $modelid = 0;
        $blklotid = 0;
        $amenityid = 0;
        $subamenityid = 0;

        if (isset($config['params']['data']['rem'])) {
            $rem = $config['params']['data']['rem'];
        }
        if (isset($config['params']['data']['refx'])) {
            $refx = $config['params']['data']['refx'];
        }
        if (isset($config['params']['data']['linex'])) {
            $linex = $config['params']['data']['linex'];
        }
        if (isset($config['params']['data']['cdrefx'])) {
            $cdrefx = $config['params']['data']['cdrefx'];
        }
        if (isset($config['params']['data']['cdlinex'])) {
            $cdlinex = $config['params']['data']['cdlinex'];
        }

        if (isset($config['params']['data']['stageid'])) {
            $stageid = $config['params']['data']['stageid'];
        }

        if (isset($config['params']['data']['solinex'])) {
            $solinex = $config['params']['data']['solinex'];
        }

        if (isset($config['params']['data']['sorefx'])) {
            $sorefx = $config['params']['data']['sorefx'];
        }

        if (isset($config['params']['data']['oslinex'])) {
            $oslinex = $config['params']['data']['oslinex'];
        }

        if (isset($config['params']['data']['osrefx'])) {
            $osrefx = $config['params']['data']['osrefx'];
        }
        if (isset($config['params']['data']['poref'])) {
            $poref = $config['params']['data']['poref'];
        }

        if (isset($config['params']['data']['itemname'])) {
            $itemdesc = $config['params']['data']['itemname'];
        }

        if (isset($config['params']['data']['sgdrate'])) {
            $sgdrate = $config['params']['data']['sgdrate'];
        } else {
            $sgdrate = $this->othersClass->getexchangerate('PHP', 'SGD');
        }



        if (isset($config['params']['data']['sjlinex'])) {
            $sjlinex = $config['params']['data']['sjlinex'];
        }

        if (isset($config['params']['data']['sjrefx'])) {
            $sjrefx = $config['params']['data']['sjrefx'];
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

        $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor, item.amt from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";

        $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
        $factor = 1;
        $retailamt = 0;
        if (!empty($item)) {
            $item[0]->factor = $this->othersClass->val($item[0]->factor);
            if ($item[0]->factor !== 0) $factor = $item[0]->factor;
            $retailamt = $item[0]->amt;
        }

        $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
        $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
        $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));


        $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor);

        $ext = number_format($computedata['ext'], $this->companysetup->getdecimal('currency', $config['params']), '.', '');
        $cost = number_format($computedata['amt'] * $forex, $this->companysetup->getdecimal('price', $config['params']), '.', '');

        $data = [
            'trno' => $trno,
            'line' => $line,
            'itemid' => $itemid,
            'rrcost' => $amt,
            'cost' => $cost,
            'rrqty' => $qty,
            'qty' => $computedata['qty'],
            'ext' => $ext,
            'disc' => $disc,
            'whid' => $whid,
            'loc' => $loc,
            'uom' => $uom,
            'void' => $void,
            'refx' => $refx,
            'linex' => $linex,
            'cdrefx' => $cdrefx,
            'cdlinex' => $cdlinex,
            'sorefx' => $sorefx,
            'solinex' => $solinex,
            'osrefx' => $osrefx,
            'oslinex' => $oslinex,
            'sjrefx' => $sjrefx,
            'sjlinex' => $sjlinex,
            'rem' => $rem,
            'ref' => $ref,
            'stageid' => $stageid
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
            if (isset($config['params']['data']['sortline'])) {
                $data['sortline'] =  $config['params']['data']['sortline'];
            } else {
                $data['sortline'] =  $data['line'];
            }


            if ($isproject) {
                if ($data['stageid'] == 0) {
                    $msg = 'Stage cannot be blank -' . $item[0]->barcode;
                    return ['status' => false, 'msg' => $msg];
                }
            }

            if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {

                $stockinfo_data = ['trno' => $trno, 'line' => $line, 'rem' => $rem];
                if (!empty($stockinfo_data)) $this->coreFunctions->sbcinsert('stockinfotrans', $stockinfo_data);

                $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' Barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' WH:' . $wh . ' Ext:' . $computedata['ext'] . ' Uom ' . $uom);
                if ($isproject) {
                    $this->updateprojmngmt($config, $stageid);
                }
                $this->loadheaddata($config);
                $row = $this->openstockline($config);
                return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.', 'line' => $line, 'reloaddata' => true];
            } else {
                return ['status' => false, 'msg' => 'Add item Failed'];
            }
        } elseif ($action == 'update') {
            $return = true;
            $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
            if ($isproject) {
                $this->updateprojmngmt($config, $stageid);
            }
            if ($refx != 0) {
                if ($this->setserveditems($refx, $linex) === 0) {
                    $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                    $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                    $this->setserveditems($refx, $linex);
                    $return = false;
                }
            }
            if ($cdrefx != 0) {
                if ($this->setservedcanvassitems($cdrefx, $cdlinex) === 0) {
                    $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                    $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                    $this->setservedcanvassitems($cdrefx, $cdlinex);
                    $return = false;
                }
            }

            if ($sorefx != 0) {
                if ($this->setservedsoitems($sorefx, $solinex) === 0) {
                    $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                    $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                    $this->setservedsoitems($sorefx, $solinex);
                    $return = false;
                }
            }
            if ($sorefx != 0) {
                if ($this->setservedsqitems($sorefx, $solinex) === 0) {
                    $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                    $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                    $this->setservedsqitems($sorefx, $solinex);
                    $return = false;
                }
            }

            if ($osrefx != 0) {
                if ($this->setservedositems($osrefx, $oslinex) === 0) {
                    $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                    $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                    $this->setservedositems($osrefx, $oslinex);
                    $return = false;
                }
            }

            if ($sjrefx != 0) {
                if ($this->setservedsjitems($sjrefx, $sjlinex) === 0) {
                    $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                    $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                    $this->setservedsjitems($sjrefx, $sjlinex);
                    $return = false;
                }
            }
            return $return;
        }
    } // end function



    public function deleteallitem($config)
    {
        $isallow = true;
        $trno = $config['params']['trno'];
        $data = $this->coreFunctions->opentable('select refx,linex,cdrefx,cdlinex,stageid,sorefx,solinex,osrefx,oslinex,sjrefx,sjlinex from ' . $this->stock . ' where trno=? and (refx<>0 or cdrefx<>0 or sorefx<>0 or osrefx<>0 or sjrefx<>0)', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from headprrem where trno=?', 'delete', [$trno]);

        foreach ($data as $key => $value) {
            if ($data[$key]->refx != 0) {
                $this->setserveditems($data[$key]->refx, $data[$key]->linex);
            } elseif ($data[$key]->cdrefx != 0) {
                $this->setservedcanvassitems($data[$key]->cdrefx, $data[$key]->cdlinex);
            }

            if (floatval($data[$key]->sorefx) != 0) {
                $this->setservedsoitems($data[$key]->sorefx, $data[$key]->solinex);
                $this->setservedsqitems($data[$key]->sorefx, $data[$key]->solinex);
            }

            if (floatval($data[$key]->osrefx) != 0) {
                $this->setservedositems($data[$key]->osrefx, $data[$key]->oslinex);
            }
            if (floatval($data[$key]->sjrefx) != 0) {
                $this->setservedsjitems($data[$key]->sjrefx, $data[$key]->sjlinex);
            }
            $this->updateprojmngmt($config, $data[$key]->stageid);
        }
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
        return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
    }


    public function deleteitem($config)
    {
        $config['params']['trno'] = $config['params']['row']['trno'];
        $config['params']['line'] = $config['params']['row']['line'];
        $config['params']['stageid'] = $config['params']['row']['stageid'];
        $data = $this->openstockline($config);
        $trno = $config['params']['trno'];
        $line = $config['params']['line'];
        $qry = "delete from " . $this->stock . " where trno=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
        $this->coreFunctions->execqry('delete from stockinfotrans where trno=? and line=?', 'delete', [$trno, $line]);
        $this->coreFunctions->execqry('delete from headprrem where trno=? and line=?', 'delete', [$trno, $line]);
        if ($data[0]->refx !== 0) {
            $this->setserveditems($data[0]->refx, $data[0]->linex);
        }
        if ($data[0]->cdrefx !== 0) {
            $this->setservedcanvassitems($data[0]->cdrefx, $data[0]->cdlinex);
        }
        if ($data[0]->sorefx !== 0) {
            $this->setservedsoitems($data[0]->sorefx, $data[0]->solinex);
            $this->setservedsqitems($data[0]->sorefx, $data[0]->solinex);
        }
        if ($data[0]->osrefx !== 0) {
            $this->setservedositems($data[0]->osrefx, $data[0]->oslinex);
        }

        if ($data[0]->sjrefx !== 0) {
            $this->setservedsjitems($data[0]->sjrefx, $data[0]->sjlinex);
        }
        $this->updateprojmngmt($config, $config['params']['stageid']);
        $data = json_decode(json_encode($data), true);
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' Barcode:' . $data[0]['barcode'] . ' Qty:' . $data[0]['rrqty'] . ' Amt:' . $data[0]['rrcost'] . ' Disc:' . $data[0]['disc'] . ' WH:' . $data[0]['wh'] . ' Ext:' . $data[0]['ext']);
        return ['status' => true, 'msg' => 'Item was successfully deleted.'];
    } // end function


    public function getcdsummary($config)
    {
        $trno = $config['params']['trno'];
        $wh = $config['params']['wh'];
        $center = $config['params']['center'];
        $rows = [];
        foreach ($config['params']['rows'] as $key => $value) {
            $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-stock.qa) as qty,stock.rrcost,
        round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        stock.disc
        FROM hcdhead as head left join hcdstock as stock on stock.trno=head.trno
        left join transnum on transnum.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and transnum.center=? and stock.qty>stock.qa and stock.void=0 and stock.status=1
    ";

            $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $center]);

            if (!empty($data)) {
                foreach ($data as $key2 => $value) {
                    $config['params']['data']['uom'] = $data[$key2]->uom;
                    $config['params']['data']['itemid'] = $data[$key2]->itemid;
                    $config['params']['trno'] = $trno;
                    $config['params']['data']['disc'] = $data[$key2]->disc;
                    $config['params']['data']['qty'] = $data[$key2]->rrqty;
                    $config['params']['data']['wh'] = $wh;
                    $config['params']['data']['loc'] = '';
                    $config['params']['data']['expiry'] = '';
                    $config['params']['data']['rem'] = '';
                    $config['params']['data']['refx'] = 0;
                    $config['params']['data']['linex'] = 0;
                    $config['params']['data']['cdrefx'] = $data[$key2]->trno;
                    $config['params']['data']['cdlinex'] = $data[$key2]->line;
                    $config['params']['data']['ref'] = $data[$key2]->docno;
                    $config['params']['data']['amt'] = $data[$key2]->rrcost;
                    $return = $this->additem('insert', $config);
                    if ($return['status']) {
                        if ($this->setservedcanvassitems($data[$key2]->trno, $data[$key2]->line) == 0) {
                            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                            $line = $return['row'][0]->line;
                            $config['params']['trno'] = $trno;
                            $config['params']['line'] = $line;
                            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                            $this->setservedcanvassitems($data[$key2]->trno, $data[$key2]->line);
                            $row = $this->openstockline($config);
                            $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
                        }
                        array_push($rows, $return['row'][0]);
                    }
                } // end foreach
            } //end if
        } //end foreach
        return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
    } //end function

    public function getprsummaryqry($config)
    {
        return "select
        head.doc,head.docno, client.clientid, client.client, client.clientname, head.address, ifnull(head.rem,'') as rem, head.cur,
        head.forex, head.shipto, head.ourref, head.yourref, head.projectid, head.terms,
        item.itemid,stock.trno, stock.line, item.barcode,stock.uom, stock.cost, (stock.qty-stock.qa) as qty,stock.rrcost,
        stock.ext, wh.clientid as whid, wh.client as wh, round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        stock.rem as srem, stock.disc,stock.stageid,head.branch,head.tax,
        head.vattype,head.yourref,head.deptid,wh.client as swh,stock.loc
      FROM hprhead as head
      left join hprstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join client as wh on wh.clientid=stock.whid
      left join client on client.client=head.client
      where stock.trno=? and stock.void=0";
    }

    public function getprsummary($config)
    {
        $companyid = $config['params']['companyid'];
        $systype = $this->companysetup->getsystemtype($config['params']);
        $trno = $config['params']['trno'];
        $wh = $config['params']['wh'];
        $center = $config['params']['center'];
        $rows = [];
        $filtercenter = " and transnum.center = '" . $center . "' ";
        foreach ($config['params']['rows'] as $key => $value) {
            $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-(stock.qa+stock.cdqa)) as qty,stock.rrcost,
        round((stock.qty-(stock.qa+stock.cdqa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        stock.disc,st.line as stageid,
        stock.projectid, stock.phaseid, stock.modelid, stock.blklotid, stock.amenityid, stock.subamenityid
        FROM hprhead as head 
        left join hprstock as stock on stock.trno=head.trno 
        left join transnum on transnum.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom left join stagesmasterfile as st on st.line = stock.stageid where stock.trno = ? " . $filtercenter . " and stock.qty>(stock.qa+stock.cdqa) and stock.void=0
    ";
            $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);
            if (!empty($data)) {
                foreach ($data as $key2 => $value) {
                    $config['params']['data']['uom'] = $data[$key2]->uom;
                    $config['params']['data']['itemid'] = $data[$key2]->itemid;
                    $config['params']['trno'] = $trno;
                    $config['params']['data']['disc'] = $data[$key2]->disc;
                    $config['params']['data']['qty'] = $data[$key2]->rrqty;
                    $config['params']['data']['wh'] = $wh;
                    $config['params']['data']['loc'] = '';
                    $config['params']['data']['expiry'] = '';
                    $config['params']['data']['rem'] = '';
                    $config['params']['data']['refx'] = $data[$key2]->trno;
                    $config['params']['data']['linex'] = $data[$key2]->line;
                    $config['params']['data']['cdrefx'] = 0;
                    $config['params']['data']['cdlinex'] = 0;
                    $config['params']['data']['stageid'] =  $data[$key2]->stageid;
                    $config['params']['data']['ref'] = $data[$key2]->docno;
                    $config['params']['data']['amt'] = $data[$key2]->rrcost;
                    if ($systype == 'REALESTATE') {

                        $config['params']['data']['projectid'] = $data[$key2]->projectid;
                        $config['params']['data']['phaseid'] = $data[$key2]->phaseid;
                        $config['params']['data']['modelid'] = $data[$key2]->modelid;
                        $config['params']['data']['blklotid'] = $data[$key2]->blklotid;
                        $config['params']['data']['amenityid'] = $data[$key2]->amenityid;
                        $config['params']['data']['subamenityid'] = $data[$key2]->subamenityid;
                    }
                    $return = $this->additem('insert', $config);
                    if ($return['status']) {
                        if ($companyid == 8) { //maxipro
                            $this->coreFunctions->sbcupdate($this->head, ['yourref' => $data[0]->docno], ['trno' => $trno]);
                        }
                        if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
                            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                            $line = $return['row'][0]->line;
                            $config['params']['trno'] = $trno;
                            $config['params']['line'] = $line;
                            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                            $this->setserveditems($data[$key2]->trno, $data[$key2]->line);
                            $row = $this->openstockline($config);
                            $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
                        }
                        array_push($rows, $return['row'][0]);
                    }
                } // end foreach
            } //end if
        } //end foreach
        return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
    } //end function


    public function getcddetails($config)
    {
        $companyid = $config['params']['companyid'];
        $trno = $config['params']['trno'];
        $wh = $config['params']['wh'];
        $center = $config['params']['center'];
        $rows = [];
        foreach ($config['params']['rows'] as $key => $value) {
            $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-stock.qa) as qty,stock.rrcost,
        round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        stock.disc
        FROM hcdhead as head left join hcdstock as stock on stock.trno=head.trno left join transnum on transnum.trno=head.trno
        left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.line=? and transnum.center=? and stock.qty>stock.qa and stock.void=0 and stock.status=1
    ";
            $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line'], $center]);
            if (!empty($data)) {
                foreach ($data as $key2 => $value) {
                    $config['params']['data']['uom'] = $data[$key2]->uom;
                    $config['params']['data']['itemid'] = $data[$key2]->itemid;
                    $config['params']['trno'] = $trno;
                    $config['params']['data']['disc'] = $data[$key2]->disc;
                    $config['params']['data']['qty'] = $data[$key2]->rrqty;
                    $config['params']['data']['wh'] = $wh;
                    $config['params']['data']['loc'] = '';
                    $config['params']['data']['expiry'] = '';
                    $config['params']['data']['rem'] = '';
                    $config['params']['data']['refx'] = 0;
                    $config['params']['data']['linex'] = 0;
                    $config['params']['data']['cdrefx'] = $data[$key2]->trno;
                    $config['params']['data']['cdlinex'] = $data[$key2]->line;
                    $config['params']['data']['ref'] = $data[$key2]->docno;
                    $config['params']['data']['amt'] = $data[$key2]->rrcost;
                    $return = $this->additem('insert', $config);
                    if ($return['status']) {
                        if ($companyid == 8) { //maxipro
                            $this->coreFunctions->sbcupdate($this->head, ['yourref' => $data[0]->docno], ['trno' => $trno]);
                        }
                        if ($this->setservedcanvassitems($data[$key2]->trno, $data[$key2]->line) == 0) {
                            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                            $line = $return['row'][0]->line;
                            $config['params']['trno'] = $trno;
                            $config['params']['line'] = $line;
                            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                            $this->setservedcanvassitems($data[$key2]->trno, $data[$key2]->line);
                            $row = $this->openstockline($config);
                            $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
                        }

                        array_push($rows, $return['row'][0]);
                    }
                } // end foreach
            } //end if
        } //end foreach
        return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
    } //end function



    public function getprdetails($config)
    {
        $trno = $config['params']['trno'];
        $companyid = $config['params']['companyid'];
        $wh = $config['params']['wh'];
        $center = $config['params']['center'];
        $rows = [];
        $filtercenter = " and transnum.center = '" . $center . "' ";
        foreach ($config['params']['rows'] as $key => $value) {
            $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-stock.qa) as qty,stock.rrcost,
        round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        stock.disc,st.line as stageid,stock.rem,stock.ext,
        stock.projectid, stock.phaseid, stock.modelid, stock.blklotid, stock.amenityid, stock.subamenityid
        FROM hprhead as head 
        left join hprstock as stock on stock.trno=head.trno
        left join transnum on transnum.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom left join stagesmasterfile as st on st.line = stock.stageid where stock.trno = ? and stock.line=? " . $filtercenter . " and stock.qty>(stock.qa+stock.cdqa) and stock.void=0
    ";
            $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
            if (!empty($data)) {
                foreach ($data as $key2 => $value) {
                    $config['params']['data']['uom'] = $data[$key2]->uom;
                    $config['params']['data']['itemid'] = $data[$key2]->itemid;
                    $config['params']['trno'] = $trno;
                    $config['params']['data']['disc'] = $data[$key2]->disc;
                    $config['params']['data']['qty'] = $data[$key2]->rrqty;
                    $config['params']['data']['wh'] = $wh;
                    $config['params']['data']['loc'] = '';
                    $config['params']['data']['expiry'] = '';
                    $config['params']['data']['rem'] = $data[$key2]->rem;
                    $config['params']['data']['refx'] = $data[$key2]->trno;
                    $config['params']['data']['linex'] = $data[$key2]->line;
                    $config['params']['data']['ref'] = $data[$key2]->docno;
                    $config['params']['data']['amt'] = $data[$key2]->rrcost;
                    $config['params']['data']['ext'] = $data[$key2]->ext;
                    $config['params']['data']['stageid'] =  $data[$key2]->stageid;
                    $return = $this->additem('insert', $config);
                    if ($return['status']) {
                        if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
                            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                            $line = $return['row'][0]->line;
                            $config['params']['trno'] = $trno;
                            $config['params']['line'] = $line;
                            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                            $this->setserveditems($data[$key2]->trno, $data[$key2]->line);
                            $row = $this->openstockline($config);
                            $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
                        }
                        array_push($rows, $return['row'][0]);
                    }
                } // end foreach
            } //end if
        } //end foreach
        return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
    } //end function



    public function getposummaryqry($config)
    {
        return
            "
        select head.docno,left(head.dateid,10) as dateid,head.client, head.clientname,
        head.ourref,stock.ref,head.address,head.rem,head.cur,head.forex,head.due,head.terms, item.itemid,
        stock.uom, stock.disc,round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        stock.trno, stock.line,stock.rrcost,'0' as stageid,'0' as projectid,head.yourref,head.wh,wh.client as swh,stock.rem
        FROM hcdhead as head 
        left join hcdstock as stock on stock.trno = head.trno 
        left join transnum on transnum.trno = head.trno
        left join item on item.itemid=stock.itemid 
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
        left join client as wh on wh.clientid=stock.whid
        where stock.trno = ? and stock.qty>stock.qa and stock.void=0 ";
    }

    public function setserveditems($refx, $linex, $void = 0)
    {
        $filter = "";


        $qry1 = "select stock." . $this->hqty . " from pohead as head left join postock as
    stock on stock.trno=head.trno where head.doc='PO' and stock.void = 0 and stock.refx=" . $refx . " and stock.linex=" . $linex;

        $qry1 = $qry1 . " union all select stock." . $this->hqty . " from hpohead left join hpostock as stock on stock.trno=
    hpohead.trno where hpohead.doc='PO' and stock.void = 0 and stock.refx=" . $refx . " and stock.linex=" . $linex;

        $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
        $qty = $this->coreFunctions->datareader($qry2);
        if ($qty === '') {
            $qty = 0;
        }
        return $this->coreFunctions->execqry("update hprstock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
    }

    public function setservedcanvassitems($cdtrno, $cdline)
    {
        $qty = 0;
        $prqty = 0;
        $qry1 = "select stock." . $this->hqty . " from pohead as head left join postock as
    stock on stock.trno=head.trno where head.doc='PO' and stock.cdrefx=" . $cdtrno . " and stock.cdlinex=" . $cdline;

        $qry1 = $qry1 . " union all select hpostock." . $this->hqty . " from hpohead left join hpostock on hpostock.trno=
    hpohead.trno where hpohead.doc='PO' and hpostock.cdrefx=" . $cdtrno . " and hpostock.cdlinex=" . $cdline;

        $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
        $qty = $this->coreFunctions->datareader($qry2);
        if ($qty === '') {
            $qty = 0;
        }
        $prtrno = 0;
        $prline = 0;
        $prtrno = $this->coreFunctions->getfieldvalue('hcdstock', 'refx', 'trno=? and line=?', [$cdtrno, $cdline]);
        if ($prtrno === '') {
            $prtrno = 0;
        }

        if ($prtrno != 0) {
            $prline = $this->coreFunctions->getfieldvalue('hcdstock', 'linex', 'trno=? and line=?', [$cdtrno, $cdline]);
            $qry1 = "select stock." . $this->hqty . " from pohead as head left join postock as
    stock on stock.trno=head.trno where head.doc='PO' and stock.refx=" . $prtrno . " and stock.linex=" . $prline;

            $qry1 = $qry1 . " union all select hpostock." . $this->hqty . " from hpohead left join hpostock on hpostock.trno=
    hpohead.trno where hpohead.doc='PO' and hpostock.refx=" . $prtrno . " and hpostock.linex=" . $prline;

            $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
            $prqty = $this->coreFunctions->datareader($qry2);
            if ($prqty === '') {
                $prqty = 0;
            }
            if ($this->coreFunctions->execqry("update hprstock set cdqa=" . $qty . ",qa=" . $prqty . " where trno=" . $prtrno . " and line=" . $prline, 'update') == 1) {
                return $this->coreFunctions->execqry("update hcdstock set qa=" . $qty . " where trno=" . $cdtrno . " and line=" . $cdline, 'update');
            } else {
                return 0;
            }
        } else {
            return $this->coreFunctions->execqry("update hcdstock set qa=" . $qty . " where trno=" . $cdtrno . " and line=" . $cdline, 'update');
        }
    } //end func

    public function setservedsoitems($refx, $linex)
    {
        $qry1 = "select stock." . $this->hqty . " from pohead as head left join postock as
    stock on stock.trno=head.trno where head.doc='PO' and stock.sorefx=" . $refx . " and stock.solinex=" . $linex;

        $qry1 = $qry1 . " union all select hpostock." . $this->hqty . " from hpohead left join hpostock on hpostock.trno=
    hpohead.trno where hpohead.doc='PO' and hpostock.sorefx=" . $refx . " and hpostock.solinex=" . $linex;

        $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
        $qty = $this->coreFunctions->datareader($qry2);
        if ($qty === '') {
            $qty = 0;
        }
        return $this->coreFunctions->execqry("update hsostock set poqa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
    }

    public function setservedsqitems($refx, $linex)
    {
        if ($refx == 0) {
            return 1;
        }
        $qryso = "select stock.iss from lahead as head left join lastock as
  stock on stock.trno=head.trno where head.doc='SJ' and stock.refx=" . $refx . " and stock.linex=" . $linex;

        $qryso = $qryso . " union all select glstock.iss from glhead left join glstock on glstock.trno=
  glhead.trno where glhead.doc='SJ' and glstock.refx=" . $refx . " and glstock.linex=" . $linex;

        $qry = "select ifnull(sum(iss),0) as value from (" . $qryso . ") as t";
        $qtysj = $this->coreFunctions->datareader($qry);
        if ($qtysj == '') {
            $qtysj = 0;
        }

        $qrypo = "select stock." . $this->hqty . " from pohead as head left join postock as
  stock on stock.trno=head.trno where head.doc='PO' and stock.sorefx=" . $refx . " and stock.solinex=" . $linex;

        $qrypo = $qrypo . " union all select stock." . $this->hqty . " from hpohead as head left join hpostock as
  stock on stock.trno=head.trno where head.doc='PO' and stock.sorefx=" . $refx . " and stock.solinex=" . $linex;

        $qry = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qrypo . ") as t";
        $qtypo = $this->coreFunctions->datareader($qry);

        if ($qtypo == '') {
            $qtypo = 0;
        }

        return $this->coreFunctions->execqry("update hqsstock set poqa=" . $qtypo . " where trno=" . $refx . " and line=" . $linex, 'update');
    }

    public function setservedositems($refx, $linex)
    {
        $qry1 = "select stock." . $this->hqty . " from pohead as head left join postock as
    stock on stock.trno=head.trno where head.doc='PO' and stock.osrefx=" . $refx . " and stock.oslinex=" . $linex;

        $qry1 = $qry1 . " union all select hpostock." . $this->hqty . " from hpohead left join hpostock on hpostock.trno=
    hpohead.trno where hpohead.doc='PO' and hpostock.osrefx=" . $refx . " and hpostock.oslinex=" . $linex;

        $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
        $qty = $this->coreFunctions->datareader($qry2);
        if (floatval($qty) == 0) {
            $qty = 0;
        }
        return $this->coreFunctions->execqry("update hosstock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
    }


    public function getlatestprice($config)
    {
        $barcode = $config['params']['barcode'];
        $client = $config['params']['client'];
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
        $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $client, $center, $barcode, $client]);

        if (!empty($data)) {
            return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
        } else {
            return ['status' => false, 'msg' => 'No Latest price found...'];
        }
    } // end function


    private function updateprojmngmt($config, $stage)
    {
        $trno = $config['params']['trno'];
        $data = $this->openstock($trno, $config);
        $proj = $this->coreFunctions->getfieldvalue($this->head, "projectid", "trno=?", [$trno]);
        $sub = $this->coreFunctions->getfieldvalue($this->head, "subproject", "trno=?", [$trno]);

        $qry1 = "select stock.ext from " . $this->head . " as head left join " . $this->stock . " as
    stock on stock.trno=head.trno where head.doc='PO' and head.projectid = " . $proj . " and head.subproject = " . $sub . " and stock.stageid=" . $stage;

        $qry1 = $qry1 . " union all select stock.ext from " . $this->hhead . " as head left join " . $this->hstock . " as stock on stock.trno=
      head.trno where head.doc='PO' and head.projectid = " . $proj . " and head.subproject = " . $sub . " and stock.stageid=" . $stage;

        $qry2 = "select ifnull(sum(ext),0) as value from (" . $qry1 . ") as t";

        $qty = $this->coreFunctions->datareader($qry2);
        if ($qty === '') {
            $qty = 0;
        }

        $editdate = $this->othersClass->getCurrentTimeStamp();
        $editby = $config['params']['user'];

        return $this->coreFunctions->execqry("update stages set po=" . $qty . ", editdate = '" . $editdate . "', editby = '" . $editby . "' where projectid = " . $proj . " and subproject=" . $sub . " and stage=" . $stage, 'update');
    }

    public function getsosummary($config)
    {
        $trno = $config['params']['trno'];
        $wh = $config['params']['wh'];
        $companyid = $config['params']['companyid'];
        $rows = [];
        $msg = '';
        foreach ($config['params']['rows'] as $key => $value) {
            $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.amt,
        (stock.iss-stock.poqa) as iss,stock.isamt,item.famt as tpdollar,
        round((stock.iss-stock.poqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc,stock.loc,stock.expiry,head.yourref
        FROM hsohead as head left join hsostock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.iss>stock.poqa and stock.void=0
    ";
            $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);
            if (!empty($data)) {
                foreach ($data as $key2 => $value) {
                    $config['params']['data']['uom'] = $data[$key2]->uom;
                    $config['params']['data']['itemid'] = $data[$key2]->itemid;
                    $config['params']['trno'] = $trno;
                    $config['params']['data']['disc'] = $data[$key2]->disc;
                    $config['params']['data']['qty'] = $data[$key2]->isqty;
                    $config['params']['data']['wh'] = $wh;
                    $config['params']['data']['loc'] = $data[$key2]->loc;
                    $config['params']['data']['expiry'] = $data[$key2]->expiry;
                    $config['params']['data']['rem'] = '';
                    $config['params']['data']['amt'] = 0;
                    $config['params']['data']['sorefx'] = $data[$key2]->trno;
                    $config['params']['data']['solinex'] = $data[$key2]->line;
                    $config['params']['data']['ref'] = $data[$key2]->docno;

                    $return = $this->additem('insert', $config);

                    if ($msg = '') {
                        $msg = $return['msg'];
                    } else {
                        $msg = $msg . $return['msg'];
                    }

                    if ($return['status']) {
                        if ($this->setservedsoitems($data[$key2]->trno, $data[$key2]->line) == 0) {
                            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                            $line = $return['row'][0]->line;
                            $config['params']['trno'] = $trno;
                            $config['params']['line'] = $line;
                            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                            $this->setservedsoitems($data[$key2]->trno, $data[$key2]->line);
                            $row = $this->openstockline($config);
                            $return = ['row' => $row, 'status' => true, 'msg' => $msg];
                        }
                        array_push($rows, $return['row'][0]);
                    }
                } // end foreach
            } //end if
        } //end foreach
        return ['row' => $rows, 'status' => true, 'msg' => $msg];
    } //end function

    public function getsodetails($config)
    {
        $trno = $config['params']['trno'];
        $wh = $config['params']['wh'];
        $companyid = $config['params']['companyid'];
        $rows = [];
        $msg = '';
        foreach ($config['params']['rows'] as $key => $value) {
            $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.amt,
        (stock.iss-stock.poqa) as iss,stock.isamt,
        round((stock.iss-stock.poqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc,stock.loc,stock.expiry,item.famt as tpdollar,head.yourref
        FROM hsohead as head left join hsostock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.line=? and stock.iss>stock.poqa and stock.void=0
    ";
            $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
            if (!empty($data)) {
                foreach ($data as $key2 => $value) {
                    $config['params']['data']['uom'] = $data[$key2]->uom;
                    $config['params']['data']['itemid'] = $data[$key2]->itemid;
                    $config['params']['trno'] = $trno;
                    $config['params']['data']['disc'] = $data[$key2]->disc;
                    $config['params']['data']['qty'] = $data[$key2]->isqty;
                    $config['params']['data']['wh'] = $wh;
                    $config['params']['data']['loc'] = $data[$key2]->loc;
                    $config['params']['data']['expiry'] = $data[$key2]->expiry;
                    $config['params']['data']['rem'] = '';
                    $config['params']['data']['amt'] = 0;
                    $config['params']['data']['sorefx'] = $data[$key2]->trno;
                    $config['params']['data']['solinex'] = $data[$key2]->line;
                    $config['params']['data']['ref'] = $data[$key2]->docno;

                    $return = $this->additem('insert', $config);
                    if ($msg = '') {
                        $msg = $return['msg'];
                    } else {
                        $msg = $msg . $return['msg'];
                    }
                    if ($return['status']) {
                        if ($this->setservedsoitems($data[$key2]->trno, $data[$key2]->line) == 0) {
                            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                            $line = $return['row'][0]->line;
                            $config['params']['trno'] = $trno;
                            $config['params']['line'] = $line;
                            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                            $this->setservedsoitems($data[$key2]->trno, $data[$key2]->line);
                            $row = $this->openstockline($config);
                            $return = ['row' => $row, 'status' => true, 'msg' => $msg];
                        }
                        array_push($rows, $return['row'][0]);
                    }
                } // end foreach
            } //end if
        } //end foreach
        return ['row' => $rows, 'status' => true, 'msg' => $msg];
    } //end function

    public function getsqposummary($config)
    {
        $trno = $config['params']['trno'];
        $wh = $config['params']['wh'];
        $rows = [];
        $msg = '';
        $sotrno = 0;
        $forex  = $this->coreFunctions->getfieldvalue($this->head, "forex", "trno=?", [$trno]);
        foreach ($config['params']['rows'] as $key => $value) {
            $qry = "
      select concat(stock.trno,stock.line) as keyid, stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, stock.uom, so.docno, date(head.dateid) as dateid,
      (stock.iss-stock.poqa) as iss,stock.isamt, stock.disc,stock.amt,stock.ext,so.trno as sotrno,
      FORMAT(((stock.iss-stock.poqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
      item.famt as tpdollar,item.amt4 as tpphp,head.yourref,stock.sortline
      from hsqhead as so left join hqshead as head on head.sotrno=so.trno left join hqsstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join transnum on transnum.trno = head.trno
      where so.doc='SQ' and stock.iss > (stock.qa+stock.sjqa+stock.poqa) and stock.void = 0 and stock.iscanvass=0 and stock.trno=?
    ";
            $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);
            if (!empty($data)) {
                foreach ($data as $key2 => $value) {
                    $config['params']['data']['uom'] = $data[$key2]->uom;
                    $config['params']['data']['itemid'] = $data[$key2]->itemid;
                    $config['params']['trno'] = $trno;
                    $config['params']['data']['disc'] = '';
                    $config['params']['data']['qty'] = $data[$key2]->isqty;
                    $config['params']['data']['wh'] = $wh;
                    $config['params']['data']['loc'] = '';
                    $config['params']['data']['expiry'] = '';
                    $config['params']['data']['rem'] = '';
                    $config['params']['data']['amt'] = 0;


                    $config['params']['data']['sorefx'] = $data[$key2]->trno;
                    $config['params']['data']['solinex'] = $data[$key2]->line;
                    $config['params']['data']['ref'] = $data[$key2]->docno;
                    $config['params']['data']['sortline'] = $data[$key2]->sortline;
                    $return = $this->additem('insert', $config);

                    if ($msg = '') {
                        $msg = $return['msg'];
                    } else {
                        $msg = $msg . $return['msg'];
                    }

                    if ($return['status']) {
                        if ($this->setservedsqitems($data[$key2]->trno, $data[$key2]->line) == 0) {
                            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                            $line = $return['row'][0]->line;
                            $config['params']['trno'] = $trno;
                            $config['params']['line'] = $line;
                            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                            $this->setservedsqitems($data[$key2]->trno, $data[$key2]->line);
                            $row = $this->openstockline($config);
                            $return = ['row' => $row, 'status' => true, 'msg' => $msg];
                        }
                        array_push($rows, $return['row'][0]);
                    }
                    $sotrno = $data[$key2]->sotrno;
                } // end foreach
                $this->coreFunctions->sbcupdate($this->head, ['sotrno' => $sotrno], ['trno' => $trno]);
            } //end if
        } //end foreach
        $this->loadheaddata($config);
        return ['row' => $rows, 'status' => true, 'msg' => $msg, 'reloaddata' => true];
    } //end function

    public function getsqdetails($config)
    {
        $trno = $config['params']['trno'];
        $wh = $config['params']['wh'];
        $rows = [];
        $msg = '';
        $forex  = $this->coreFunctions->getfieldvalue($this->head, "forex", "trno=?", [$trno]);
        foreach ($config['params']['rows'] as $key => $value) {
            $qry = "
      select concat(stock.trno,stock.line) as keyid, stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, stock.uom, so.docno, date(head.dateid) as dateid,
      (stock.iss-stock.poqa) as iss,stock.isamt, stock.disc,stock.amt,stock.ext,
      FORMAT(((stock.iss-stock.poqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,item.famt as tpdollar,head.yourref,item.amt4 as tpphp,stock.sortline
      from hsqhead as so left join hqshead as head on head.sotrno=so.trno left join hqsstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join transnum on transnum.trno = head.trno
      where so.doc='SQ' and stock.iss > (stock.qa+stock.sjqa+stock.poqa) and stock.void = 0 and stock.iscanvass=0 and stock.trno=? and stock.line=?
    ";
            $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
            if (!empty($data)) {
                foreach ($data as $key2 => $value) {
                    $config['params']['data']['uom'] = $data[$key2]->uom;
                    $config['params']['data']['itemid'] = $data[$key2]->itemid;
                    $config['params']['trno'] = $trno;
                    $config['params']['data']['disc'] = '';
                    $config['params']['data']['qty'] = $data[$key2]->isqty;
                    $config['params']['data']['wh'] = $wh;
                    $config['params']['data']['loc'] = '';
                    $config['params']['data']['expiry'] = '';
                    $config['params']['data']['rem'] = '';
                    $config['params']['data']['amt'] = 0;

                    $config['params']['data']['sorefx'] = $data[$key2]->trno;
                    $config['params']['data']['solinex'] = $data[$key2]->line;
                    $config['params']['data']['ref'] = $data[$key2]->docno;
                    $config['params']['data']['sortline'] = $data[$key2]->sortline;
                    $return = $this->additem('insert', $config);
                    if ($msg = '') {
                        $msg = $return['msg'];
                    } else {
                        $msg = $msg . $return['msg'];
                    }
                    if ($return['status']) {
                        if ($this->setservedsqitems($data[$key2]->trno, $data[$key2]->line) == 0) {
                            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                            $line = $return['row'][0]->line;
                            $config['params']['trno'] = $trno;
                            $config['params']['line'] = $line;
                            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                            $this->setservedsqitems($data[$key2]->trno, $data[$key2]->line);
                            $row = $this->openstockline($config);
                            $return = ['row' => $row, 'status' => true, 'msg' => $msg];
                        }
                        array_push($rows, $return['row'][0]);
                    }
                } // end foreach
            } //end if
        } //end foreach
        return ['row' => $rows, 'status' => true, 'msg' => $msg];
    } //end function

    public function getcriticalstocks($config)
    {
        $trno = $config['params']['trno'];
        $wh = $config['params']['wh'];
        $rows = [];
        $msg = '';

        $data = $config['params']['rows'];

        foreach ($data as $key => $value) {

            $latestcost = $this->othersClass->getlatestcostTS($config, $value['barcode'], '', $config['params']['center'], $trno);
            if ($latestcost['status']) {
                $amt = $latestcost['data'][0]->amt;
            } else {
                $amt = 0;
            }

            $config['params']['data']['uom'] = $value['uom'];
            $config['params']['data']['itemid'] = $value['itemid'];
            $config['params']['trno'] = $trno;
            $config['params']['data']['disc'] = '';
            $config['params']['data']['amt'] = $amt;
            $config['params']['data']['qty'] = $value['reorder'] + $value['sobal'] - $value['pobal'];
            $config['params']['data']['wh'] = $wh;
            $config['params']['data']['rem'] = '';
            $config['params']['data']['ref'] = '';
            $config['params']['data']['loc'] = '';
            $return = $this->additem('insert', $config);
            if ($return['status']) {
                $line = $return['row'][0]->line;
                $config['params']['trno'] = $trno;
                $config['params']['line'] = $line;
                $row = $this->openstockline($config);
                $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
                array_push($rows, $return['row'][0]);
            }
        }

        return ['row' => $rows, 'status' => true, 'msg' => $msg];
    }

    private function autocreatestock($config, $data)
    {
        $trno = $config['params']['trno'];
        $sotrno = $data['sotrno'];
        $wh = $data['wh'];
        $rows = [];
        $msg = '';
        $forex  = $this->coreFunctions->getfieldvalue($this->head, "forex", "trno=?", [$trno]);
        $qry = "select concat(stock.trno,stock.line) as keyid, stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, stock.uom, so.docno, date(head.dateid) as dateid,
      (stock.iss-stock.poqa) as iss,stock.isamt, stock.disc,stock.amt,stock.ext,
      FORMAT(((stock.iss-stock.poqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
      item.famt as tpdollar,head.yourref,item.amt4 as tpphp
      from hsqhead as so left join hqshead as head on head.sotrno=so.trno left join hqsstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join transnum on transnum.trno = head.trno
      where so.doc='SQ' and stock.iss > (stock.qa+stock.sjqa+stock.poqa) and stock.void = 0 and stock.iscanvass=0 and so.trno=?
    ";
        $data2 = $this->coreFunctions->opentable($qry, [$sotrno]);
        if (!empty($data2)) {
            foreach ($data2 as $key2 => $value) {
                $config['params']['data']['uom'] = $data2[$key2]->uom;
                $config['params']['data']['itemid'] = $data2[$key2]->itemid;
                $config['params']['trno'] = $trno;
                $config['params']['data']['disc'] = '';
                $config['params']['data']['qty'] = $data2[$key2]->isqty;
                $config['params']['data']['wh'] = $wh;
                $config['params']['data']['loc'] = '';
                $config['params']['data']['expiry'] = '';
                $config['params']['data']['rem'] = '';
                $config['params']['data']['amt'] = 0;

                $config['params']['data']['sorefx'] = $data2[$key2]->trno;
                $config['params']['data']['solinex'] = $data2[$key2]->line;
                $config['params']['data']['ref'] = $data2[$key2]->docno;
                $return = $this->additem('insert', $config);

                if ($msg = '') {
                    $msg = $return['msg'];
                } else {
                    $msg = $msg . $return['msg'];
                }

                if ($return['status']) {
                    if ($this->setservedsqitems($data2[$key2]->trno, $data2[$key2]->line) == 0) {
                        $datax = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                        $line = $return['row'][0]->line;
                        $config['params']['trno'] = $trno;
                        $config['params']['line'] = $line;
                        $this->coreFunctions->sbcupdate($this->stock, $datax, ['trno' => $trno, 'line' => $line]);
                        $this->setservedsqitems($data2[$key2]->trno, $data2[$key2]->line);
                        $row = $this->openstockline($config);
                        $return = ['row' => $row, 'status' => true, 'msg' => $msg];
                    }
                    array_push($rows, $return['row'][0]);
                }
            } // end foreach
            return ['row' => $rows, 'status' => true, 'msg' => 'Item was successfully added.', 'reloaddata' => true];
        } //end if

    }

    public function getossummary($config)
    {
        $trno = $config['params']['trno'];
        $wh = $config['params']['wh'];
        $rows = [];
        $msg = '';
        foreach ($config['params']['rows'] as $key => $value) {
            $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-stock.qa) as qty,stock.rrcost,item.famt as tpdollar,
        round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        stock.disc,stock.loc
        FROM hoshead as head left join hosstock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.qty>stock.qa and stock.void=0
    ";
            $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);
            if (!empty($data)) {
                foreach ($data as $key2 => $value) {
                    $config['params']['data']['uom'] = $data[$key2]->uom;
                    $config['params']['data']['itemid'] = $data[$key2]->itemid;
                    $config['params']['trno'] = $trno;
                    $config['params']['data']['disc'] = $data[$key2]->disc;
                    $config['params']['data']['qty'] = $data[$key2]->rrqty;
                    $config['params']['data']['wh'] = $wh;
                    $config['params']['data']['loc'] = $data[$key2]->loc;
                    $config['params']['data']['rem'] = '';
                    $config['params']['data']['amt'] = $data[$key2]->rrcost;
                    $config['params']['data']['osrefx'] = $data[$key2]->trno;
                    $config['params']['data']['oslinex'] = $data[$key2]->line;
                    $config['params']['data']['ref'] = $data[$key2]->docno;

                    $return = $this->additem('insert', $config);

                    if ($msg = '') {
                        $msg = $return['msg'];
                    } else {
                        $msg = $msg . $return['msg'];
                    }

                    if ($return['status']) {
                        if ($this->setservedositems($data[$key2]->trno, $data[$key2]->line) == 0) {
                            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                            $line = $return['row'][0]->line;
                            $config['params']['trno'] = $trno;
                            $config['params']['line'] = $line;
                            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                            $this->setservedositems($data[$key2]->trno, $data[$key2]->line);
                            $row = $this->openstockline($config);
                            $return = ['row' => $row, 'status' => true, 'msg' => $msg];
                        }
                        array_push($rows, $return['row'][0]);
                    }
                } // end foreach
            } //end if
        } //end foreach
        return ['row' => $rows, 'status' => true, 'msg' => $msg];
    } //end function

    public function getosdetails($config)
    {
        $trno = $config['params']['trno'];
        $wh = $config['params']['wh'];
        $rows = [];
        $msg = '';
        foreach ($config['params']['rows'] as $key => $value) {
            $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-stock.qa) as qty,stock.rrcost,
        round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        stock.disc,stock.loc,item.famt as tpdollar
        FROM hoshead as head left join hosstock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.line=? and stock.qty>stock.qa and stock.void=0
    ";
            $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
            if (!empty($data)) {
                foreach ($data as $key2 => $value) {
                    $config['params']['data']['uom'] = $data[$key2]->uom;
                    $config['params']['data']['itemid'] = $data[$key2]->itemid;
                    $config['params']['trno'] = $trno;
                    $config['params']['data']['disc'] = $data[$key2]->disc;
                    $config['params']['data']['qty'] = $data[$key2]->rrqty;
                    $config['params']['data']['wh'] = $wh;
                    $config['params']['data']['loc'] = $data[$key2]->loc;
                    $config['params']['data']['rem'] = '';
                    $config['params']['data']['amt'] = $data[$key2]->rrcost;
                    $config['params']['data']['osrefx'] = $data[$key2]->trno;
                    $config['params']['data']['oslinex'] = $data[$key2]->line;
                    $config['params']['data']['ref'] = $data[$key2]->docno;
                    $return = $this->additem('insert', $config);
                    if ($msg = '') {
                        $msg = $return['msg'];
                    } else {
                        $msg = $msg . $return['msg'];
                    }
                    if ($return['status']) {
                        if ($this->setservedositems($data[$key2]->trno, $data[$key2]->line) == 0) {
                            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                            $line = $return['row'][0]->line;
                            $config['params']['trno'] = $trno;
                            $config['params']['line'] = $line;
                            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                            $this->setservedositems($data[$key2]->trno, $data[$key2]->line);
                            $row = $this->openstockline($config);
                            $return = ['row' => $row, 'status' => true, 'msg' => $msg];
                        }
                        array_push($rows, $return['row'][0]);
                    }
                } // end foreach
            } //end if
        } //end foreach
        return ['row' => $rows, 'status' => true, 'msg' => $msg];
    } //end function


    public function recomputecost($head, $config)
    {
        $data = $this->openstock($head['trno'], $config);
        $data2 = json_decode(json_encode($data), true);
        $exec = true;
        foreach ($data2 as $key => $value) {
            $damt = $this->othersClass->sanitizekeyfield('amt', $data2[$key][$this->damt]);
            $dqty = round($this->othersClass->sanitizekeyfield('qty', $data2[$key][$this->dqty]), $this->companysetup->getdecimal('qty', $config['params']));

            if ($this->companysetup->getvatexpurch($config['params'])) {
                $computedata = $this->othersClass->computestock($damt * $head['forex'], $data[$key]->disc, $dqty, $data[$key]->uomfactor, 0, 'P');
            } else {
                $computedata = $this->othersClass->computestock($damt * $head['forex'], $data[$key]->disc, $dqty, $data[$key]->uomfactor, $head['tax'], 'P');
            }
            $exec = $this->coreFunctions->execqry("update postock set cost = " . $computedata['amt'] . " where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
        }
        return $exec;
    }

    public function getsjsummary($config)
    {
        $trno = $config['params']['trno'];
        $wh = $this->coreFunctions->getfieldvalue($this->head, 'wh', 'trno=?', [$trno]);
        $config['params']['client'] = $this->coreFunctions->getfieldvalue($this->head, 'client', 'trno=?', [$trno]);
        $rows = [];
        foreach ($config['params']['rows'] as $key => $value) {
            $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.iss-stock.poqa) as iss,stock.isamt,
        round((stock.iss-stock.poqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc,stock.loc,stock.expiry,stock.projectid,stock.rem 
        FROM glhead as head left join glstock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.iss>stock.poqa and stock.void=0
    ";
            $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);
            if (!empty($data)) {
                foreach ($data as $key2 => $value) {
                    $config['params']['data']['uom'] = $data[$key2]->uom;
                    $config['params']['data']['itemid'] = $data[$key2]->itemid;
                    $config['params']['trno'] = $trno;
                    $config['params']['data']['disc'] = '';
                    $config['params']['data']['qty'] = $data[$key2]->isqty;
                    $config['params']['data']['wh'] = $wh;
                    $config['params']['data']['loc'] = $data[$key2]->loc;
                    $config['params']['data']['expiry'] = $data[$key2]->expiry;
                    $config['params']['data']['rem'] =  $data[$key2]->rem;
                    $config['params']['data']['sjrefx'] = $data[$key2]->trno;
                    $config['params']['data']['sjlinex'] = $data[$key2]->line;
                    $config['params']['data']['ref'] = $data[$key2]->docno;
                    $config['params']['data']['amt'] = 0;
                    $config['params']['data']['projectid'] = $data[$key2]->projectid;

                    $return = $this->additem('insert', $config);
                    if ($return['status']) {
                        if ($this->setservedsjitems($data[$key2]->trno, $data[$key2]->line) == 0) {
                            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                            $line = $return['row'][0]->line;
                            $config['params']['trno'] = $trno;
                            $config['params']['line'] = $line;
                            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                            $this->setservedsjitems($data[$key2]->trno, $data[$key2]->line);
                            $row = $this->openstockline($config);
                            $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
                        }
                        array_push($rows, $return['row'][0]);
                    }
                } // end foreach
            } //end if
        } //end foreach
        return ['row' => $rows, 'status' => true, 'msg' => 'Added Items Successful...'];
    } //end function


    public function getsjdetails($config)
    {
        $trno = $config['params']['trno'];
        $wh = $config['params']['wh'];
        $companyid = $config['params']['companyid'];
        $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno = ?', [$trno]);
        $dateid = $this->coreFunctions->getfieldvalue($this->head, 'dateid', 'trno=?', [$trno]);
        $config['params']['client'] = $this->coreFunctions->getfieldvalue($this->head, 'client', 'trno=?', [$trno]);
        $rows = [];
        foreach ($config['params']['rows'] as $key => $value) {
            $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.iss-stock.poqa) as iss,stock.isamt,
        round((stock.iss-stock.poqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc,stock.loc,stock.expiry,stock.projectid,stock.rem
        FROM glhead as head left join glstock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.line=? and stock.iss>stock.poqa and stock.void=0
    ";
            $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
            if (!empty($data)) {
                foreach ($data as $key2 => $value) {
                    $config['params']['data']['uom'] = $data[$key2]->uom;
                    $config['params']['data']['itemid'] = $data[$key2]->itemid;
                    $config['params']['trno'] = $trno;
                    $config['params']['data']['qty'] = $data[$key2]->isqty;
                    $config['params']['data']['wh'] = $wh;
                    $config['params']['data']['loc'] = $data[$key2]->loc;
                    $config['params']['data']['expiry'] = $data[$key2]->expiry;
                    $config['params']['data']['rem'] =  $data[$key2]->rem;
                    $config['params']['data']['sjrefx'] = $data[$key2]->trno;
                    $config['params']['data']['sjlinex'] = $data[$key2]->line;
                    $config['params']['data']['ref'] = $data[$key2]->docno;
                    $config['params']['data']['projectid'] = $data[$key2]->projectid;
                    $config['params']['data']['amt'] = 0;
                    $config['params']['data']['disc'] = '';
                    $config['params']['barcode'] = $data[$key2]->barcode;

                    $return = $this->additem('insert', $config);
                    if ($return['status']) {
                        if ($this->setservedsjitems($data[$key2]->trno, $data[$key2]->line) == 0) {
                            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                            $line = $return['row'][0]->line;
                            $config['params']['trno'] = $trno;
                            $config['params']['line'] = $line;
                            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                            $this->setservedsjitems($data[$key2]->trno, $data[$key2]->line);
                            $row = $this->openstockline($config);
                            $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
                        }
                        array_push($rows, $return['row'][0]);
                    }
                } // end foreach
            } //end if
        } //end foreach
        return ['row' => $rows, 'status' => true, 'msg' => 'Added Items Successful...'];
    } //end function

    public function setservedsjitems($refx, $linex)
    {
        $qry = "select stock." . $this->hqty . " from pohead as head left join postock as stock on stock.trno=head.trno where stock.sjrefx=" . $refx . " and stock.sjlinex=" . $linex . "
    union all
    select stock." . $this->hqty . " from hpohead as head left join hpostock as stock on stock.trno=head.trno where stock.sjrefx=" . $refx . " and stock.sjlinex=" . $linex;
        $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry . ") as t";
        $qty = $this->coreFunctions->datareader($qry2, [], '', true);
        $result = $this->coreFunctions->execqry("update glstock set poqa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
        $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from glstock where trno=? and qty>poqa", [$refx], '', true);

        return $result;
    }

    public function sbcscript($config)
    {
        return true;
    }

    // start
    public function reportsetup($config)
    {
        $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
        $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

        $modulename = $this->modulename;
        $data = [];
        $isreload = false;
        $isposted = $this->othersClass->isposted2($config['params']['trno'], $this->tablenum);
        if (!$isposted) {
            $isreload = true;
            $this->posttrans($config);
        }

        $style = 'width:500px;max-width:500px;';
        return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false, 'showemailbtn' => true, 'reloadhead' => $isreload];
    }

    public function reportdata($config)
    {
        $companyid = $config['params']['companyid'];
        $this->logger->sbcviewreportlog($config);
        $config['params']['trno'] = $config['params']['dataid'];
        $dataparams = $config['params']['dataparams'];


        if (isset($dataparams['checked'])) $this->othersClass->writeSignatories($config, 'checked', $dataparams['checked']);
        if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
        if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);
        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
        // }

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }
    // end


    public function sendemail($config)
    {
        $dataparams = json_decode($config['params']['dataparams']);
        $data['params'] = $config['params'];
        $data['params']['fromemail'] = true;
        $datenow = $this->othersClass->getCurrentDate();
        $expirydays = $this->companysetup->getvalidfiledate($config['params']);
        $data['params']['expiration'] = date('Y-m-d', strtotime($datenow . ' + ' . $expirydays . ' days'));
        $linkData = urlencode($this->othersClass->encryptString(json_encode($data)));

        $data2 = [];
        $data2['params'] = $config['params'];
        $data2['params']['fromemail'] = true;
        $data2['params']['expiration'] = date('Y-m-d', strtotime($datenow . ' + ' . $expirydays . ' days'));
        $attachments = [];
        $hasattachment = false;
        $att = $this->coreFunctions->opentable("select trno, line, picture, title from transnum_picture where trno=" . $data['params']['dataid']);
        $links = '';
        $mainfolder = '/images/';
        if (!empty($att)) {
            $hasattachment = true;
            foreach ($att as $akey => $a) {
                $data2['params']['attachment_data']['trno'] = $a->trno;
                $data2['params']['attachment_data']['line'] = $a->line;
                $data2['params']['attachment_data']['picture'] = $a->picture;
                $data2['params']['attachment_data']['title'] = $a->title;
                $data2['params']['attachment_data']['tablename'] = 'transnum_picture';
                $data2['reporttype'] = 'module_attachment';
                $link = urlencode($this->othersClass->encryptString(json_encode($data2)));
                $links .= "<a href='" . env('APP_URL') . "/getFile?id=" . $link . "'>" . $a->title . "</a><br>";
            }
        }

        $msg = "<html>
      <body>
        <a href='" . env('APP_URL') . "/getFile?id=" . $linkData . "'>Click here to download attachment.</a><br>
        " . $links . "
      </body>
    </html>";

        $emailinfo = [
            'email' => 'solutionbasecorp@yahoo.com',
            'view' => 'emails.welcome',
            'msg' => $msg,
            'filename' => 'po',
            'title' => 'Purchase Order',
            'subject' => 'Purchase Order',
            'name' => 'Name 1',
        ];

        return $this->othersClass->sbcsendemail($config, $emailinfo);
    }
} //end class
