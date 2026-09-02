<?php

namespace App\Http\Classes\modules\a5ce0dd7c60273e71ccf80f476f58068;

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

class pr // class declaration
{

    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'PURCHASE REQUISITION';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
    public $tablenum = 'transnum';
    public $head = 'prhead'; // current transaction header table
    public $hhead = 'hprhead'; // history header table
    public $stock = 'prstock'; // current transaction stock table
    public $hstock = 'hprstock'; // history stock table 
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log'; // transaction logs for deleted documents
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
        'purtype',
        'requestor'
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
        ['val' => 'forapproval', 'label' => 'For Approval', 'color' => 'primary'],
        ['val' => 'posted', 'label' => 'Posted', 'color' => 'orange']
    ];

    /**
     * initializes all helper classes needed for the module
     */
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

    /**
     * returns the attribute of the module such as access id for view, edit, delete, print, etc. 
     * this will be used in checking the access of the user in doing certain actions in the module
     */
    public function getAttrib()
    {
        $attrib = array(
            'view' => 619,
            'edit' => 620,
            'new' => 621,
            'save' => 622,
            // 'change' => 623, remove change doc
            'delete' => 624,
            'print' => 625,
            'lock' => 626,
            'unlock' => 627,
            'changeamt' => 628,
            'post' => 630,
            'unpost' => 631,
            'additem' => 814,
            'edititem' => 815,
            'deleteitem' => 816,
            'voiditem' => 3601,
            'forapproval' => 5944
        );
        return $attrib;
    }

    public function createHeadbutton($config) // creating of buttons in the header
    {
        $btns = array(
            'load',
            'new',
            'save',
            'delete',
            'cancel',
            'print',
            'lock',
            'unlock',
            'post',
            'unpost',
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
        $step3 = $this->helpClass->getFields(['btnadditem', 'btnquickadd', 'rrqty', 'uom', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
        $step4 = $this->helpClass->getFields(['rrqty', 'uom', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
        $step5 = $this->helpClass->getFields(['btnstockdelete', 'btndeleteallitem']);
        $step6 = $this->helpClass->getFields(['btndelete']);


        // $buttons['help']['items'] = [
        //   'create' => ['label' => 'How to create New Document', 'action' => $step1],
        //   'edit' => ['label' => 'How to edit details from the header', 'action' => $step2],
        //   'additem' => ['label' => 'How to add item/s', 'action' => $step3],
        //   'edititem' => ['label' => 'How to edit item details', 'action' => $step4],
        //   'deleteitem' => ['label' => 'How to delete item/s', 'action' => $step5],
        //   'deletehead' => ['label' => 'How to delete whole transaction', 'action' => $step6]
        // ];

        $buttons['others']['items'] = [
            'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
            'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
            'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
            'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
        ];

        if ($this->companysetup->getisshowmanual($config['params'])) {
            $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => $config['params']['doc'], 'title' => strtoupper($this->modulename) . '_MANUAL', 'action' => 'viewpdf', 'access' => 'view', 'type' => 'viewmanual']];
        }
        return $buttons;
    } // createHeadbutton

    public function createHeadField($config) // creating of fields in the header
    {
        $systemtype = $this->companysetup->getsystemtype($config['params']);
        $viewall = $this->othersClass->checkAccess($config['params']['user'], 4453);
        $noeditdate = $this->othersClass->checkAccess($config['params']['user'], 4851);

        $fields = ['docno', 'client', 'clientname', 'address'];

        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'client.label', 'Department');
        data_set($col1, 'client.lookupclass', 'replookupdepartment');

        data_set($col1, 'docno.label', 'Transaction#');

        $fields = ['dateid', 'due'];

        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'due.label', 'Required Date');

        if ($noeditdate) {
            data_set($col2, 'dateid.class', 'sbccsreadonly');
        }

        $fields = [['yourref', 'ourref'], 'rem'];
        if ($this->companysetup->getistodo($config['params'])) {
            array_push($fields, 'donetodo');
        }

        $col3 = $this->fieldClass->create($fields);

        $fields = ['forapproval'];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'forapproval.access', 'forapproval');
        return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
    }

    /**
     * creates the column of the grid and the buttons in the grid. 
     * it also sets the type, label, style, and other attributes of the column.
     */
    public function createTab($access, $config) // creating of column in the grid
    {
        $sq_makepo = $this->othersClass->checkAccess($config['params']['user'], 2873);
        $sq_makejo = $this->othersClass->checkAccess($config['params']['user'], 3984);
        $pr_btnvoid_access = $this->othersClass->checkAccess($config['params']['user'], 3601);

        $action = 0;
        $itemdesc = 1;
        $rrqty = 2;
        $rrcost = 3;
        $uom = 4;
        $disc = 5;
        $netamt = 6;
        $ext = 7;
        $qa = 8;
        $rem = 9;
        $wh = 10;
        $whname = 11;
        $void = 12;
        $itemname = 13;
        $partno = 14;
        $subcode = 15;
        $boxcount = 16;
        $barcode = 17;


        $headgridbtns = ['itemvoiding', 'viewref', 'viewdiagram'];

        if ($pr_btnvoid_access == 0) {
            unset($headgridbtns[0]);
        }

        $tab = [
            $this->gridname => [
                'gridcolumns' => [
                    'action',
                    'itemdescription',
                    'rrqty',
                    'rrcost',
                    'uom',
                    'disc',
                    'netamt',
                    'ext',
                    'qa',
                    'rem',
                    'wh',
                    'whname',
                    'void',
                    'itemname',
                    'partno',
                    'subcode',
                    'boxcount',
                    'barcode'
                ],
                'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'],
                'headgridbtns' => $headgridbtns
            ]
        ];


        $stockbuttons = ['save', 'delete', 'showbalance'];

        if ($this->companysetup->getiseditsortline($config['params'])) {
            array_push($stockbuttons, 'sortline');
        }

        // 7 - ref
        $obj[0]['inventory']['columns'][$itemname]['lookupclass'] = 'refpo';

        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        if (!$access['changeamt']) {
            $obj[0]['inventory']['columns'][$qa]['readonly'] = true;
            $obj[0]['inventory']['columns'][$rem]['readonly'] = true;
        }

        $obj[0]['inventory']['columns'][$partno]['label'] = 'Part No.';
        $obj[0]['inventory']['columns'][$partno]['type'] = 'label';
        $obj[0]['inventory']['columns'][$partno]['align'] = 'left';
        $obj[0]['inventory']['columns'][$partno]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';

        $obj[0]['inventory']['columns'][$subcode]['label'] = 'Old SKU';
        $obj[0]['inventory']['columns'][$subcode]['type'] = 'label';
        $obj[0]['inventory']['columns'][$subcode]['align'] = 'left';
        $obj[0]['inventory']['columns'][$subcode]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';

        $obj[0]['inventory']['columns'][$boxcount]['label'] = 'QTY Per Box';
        $obj[0]['inventory']['columns'][$boxcount]['type'] = 'label';
        $obj[0]['inventory']['columns'][$boxcount]['align'] = 'left';
        $obj[0]['inventory']['columns'][$boxcount]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';

        $obj[0]['inventory']['columns'][$partno]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$subcode]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$boxcount]['type'] = 'coldel';

        $obj[0]['inventory']['columns'][$whname]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$wh]['type'] = 'hidden';
        $obj[0]['inventory']['columns'][$wh]['label'] = '';
        $obj[0]['inventory']['columns'][$wh]['style'] = 'width: 20px;whiteSpace: normal;min-width:20px;max-width:20px';

        $obj[0]['inventory']['columns'][$netamt]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$ext]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$disc]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$rrcost]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
        $obj[0]['inventory']['columns'][$barcode]['label'] = '';

        $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    //     public function createtab2($access, $config)
    //   {
    //     $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    //     $obj = $this->tabClass->createtab($tab, []);

    //     $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

    //     if ($this->companysetup->getistodo($config['params'])) {
    //       $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrytransnumtodo', 'label' => 'To Do', 'access' => 'view']];
    //       $objtodo = $this->tabClass->createtab($tab, []);
    //       $return['To Do'] = ['icon' => 'fa fa-list', 'tab' => $objtodo];
    //     }

    //     return $return;
    //   } // createtab2

    public function createtabbutton($config) // creating of buttons in the grid
    {
        $tbuttons = ['additem', 'quickadd', 'saveitem', 'deleteallitem'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    /**
     * creates the column of the document listing and the buttons in the document listing. 
     */
    public function createdoclisting() // creating of column in document listing
    {
        $action = 0;
        $liststatus = 1;
        $listdocument = 2;
        $listdate = 3;
        $listclientname = 4;
        $yourref = 5;
        $ourref = 6;
        $postdate = 7;
        $listpostedby = 8;
        $listcreateby = 9;
        $listeditby = 10;
        $listviewby = 11;

        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname'];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$liststatus]['name'] = 'statuscolor';
        $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $cols[$yourref]['align'] = 'text-left';
        $cols[$ourref]['align'] = 'text-left';
        $cols[$postdate]['label'] = 'Post Date';
        return $cols;
    }

    /**
     * this function is used to load the data in the document listing based on the parameters passed such as date, document type, item filter, and other parameters. 
     * it will return the data to be displayed in the document listing.
     */
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
            $searchfield = ['head.docno', 'head.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        } else {
            $limit = 'limit 25';
        }

        $dateid = "left(head.dateid,10) as dateid,head.dateid as date2 ";

        // Define status color logic
        $lscolor = "'red'";
        $lstatus = "DRAFT";

        switch ($itemfilter) {
            case 'draft':
                $condition = ' and num.postdate is null and head.lockdate is null ';
                $lstatus = "'DRAFT'";
                break;
            case 'locked':
                $condition = ' and num.postdate is null and head.lockdate is not null and hi.checkdate is null';
                $lstatus = "'LOCKED'";
                break;
            case 'forapproval':
                $condition = 'and head.lockdate is not null and hi.checkdate is not null and num.statid = 10 and num.postdate is null';
                $lstatus = "'FOR APPROVAL'";
                break;
            case 'posted':
                $condition = ' and num.postdate is not null ';
                $lstatus = "'POSTED'";
                break;
        }

        $qry = "select head.trno,head.docno,head.clientname,$dateid,
        " . $lstatus . " as status, date(num.postdate) as postdate,
        head.createby,head.editby,head.viewby,num.postedby, 
        head.yourref, head.ourref,
        case ifnull(head.lockdate,'') when '' then $lscolor else 'green' end as statuscolor  
        from " . $this->head . " as head 
        left join " . $this->tablenum . " as num on num.trno=head.trno 
        left join headinfotrans as hi on hi.trno = head.trno
        left join trxstatus as status on status.line = num.statid
        " . $join . "
        where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " " . $filtersearch . "
        union all
        select head.trno,head.docno,head.clientname,$dateid, 
        'POSTED' as status, date(num.postdate) as postdate,
        head.createby,head.editby,head.viewby, num.postedby, 
        head.yourref, head.ourref,
        'grey' as statuscolor  
        from " . $this->hhead . " as head 
        left join " . $this->tablenum . " as num on num.trno=head.trno
        left join headinfotrans as hi on hi.trno = head.trno 
        left join trxstatus as status on status.line = num.statid
        " . $hjoin . "
        where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " " . $filtersearch . "
        order by date2 desc,docno desc $limit";

        $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    } // end function loaddoclisting
    /**
     * this function is used to load the data in the header of the transaction based on the transaction number passed. 
     * it will return the data to be displayed in the header and also the data to be displayed in the grid.
     */
    public function loadheaddata($config) // loading of data in the header
    {
        $doc = $config['params']['doc'];
        $center = $config['params']['center'];
        $trno = $config['params']['trno'];
        $tablenum = $this->tablenum;

        /* if trno is 0 it means that the transaction is new and has no transaction number yet, so we will generate a new transaction number. 
         if trno is not 0 it means that the transaction already has a transaction number and we will check if the transaction number is valid and can be loaded. 
        */
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
        head.agent,
        head.purtype,
        head.requestor,
        req.clientname as requestorname,
        agent.clientname as agentname,
        head.wh as wh,
        warehouse.clientname as whname,
        '' as dwhname,
        left(head.due,10) as due,
        head.lockdate,
        hi.checkdate,
        num.postdate,
        client.groupid";

        $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join client as req on req.clientid = head.requestor
        left join headinfotrans as hi on hi.trno = head.trno
        where head.trno = ? and num.center = ?
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join client as req on req.clientid = head.requestor
        left join headinfotrans as hi on hi.trno = head.trno
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

            // Hide forapproval unless lockdate is set AND checkdate is not yet set
            $lockdate = isset($head[0]->lockdate) ? $head[0]->lockdate : null;
            $checkdate = isset($head[0]->checkdate) ? $head[0]->checkdate : null;
            $postdate = isset($head[0]->postdate) ? $head[0]->postdate : null;

            $hideforapproval = true;

            if (!empty($lockdate)) {
                $hideforapproval = false;
                if (!empty($checkdate) || !empty($postdate)) {
                    $hideforapproval = true;
                }
            }

            $hideobj['forapproval'] = $hideforapproval;

            if ($this->companysetup->getistodo($config['params'])) {
                $btndonetodo = $this->othersClass->checkdonetodo($config, $tablenum);
                // $hideobj = ['donetodo' => !$btndonetodo];
                $hideobj['donetodo'] = !$btndonetodo;
            }

            return [
                'head' => $head, // header data
                'griddata' => ['inventory' => $stock], // line items
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

    /**
     * this function is used to create a new transaction with default values in the header. 
     * it will return the data to be displayed in the header 
     * and also the data to be displayed in the grid which is empty since it's a new transaction.
     */
    public function createnewtransaction($docno, $params) // Initializes a blank PR form with default values
    {
        $branch = $params['center'];
        $viewall = $this->othersClass->checkAccess($params['user'], 4453);
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
        $data[0]['requestor'] = 0;
        $data[0]['requestorname'] = '';
        $data[0]['requestorcode'] = '';
        $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
        $data[0]['wh'] = $this->companysetup->getwh($params);
        $name = $this->coreFunctions->datareader("select clientname as value from client where client='" . $data[0]['wh'] . "'");
        $data[0]['whname'] = $name;
        $data[0]['address'] = '';
        $data[0]['purtype'] = '';
        return $data;
    } // end function createnewtransaction

    /**
     * this function is used to load the data in the grid based on the transaction number passed. 
     * Retrieves all line items for a PR transaction
     */
    public function openstock($trno, $config)
    {
        $sqlselect = $this->getstockselect($config); // calling getstockseelct function to get the select part of the query

        $qry = $sqlselect . "
        FROM $this->stock as stock
        left join item on item.itemid=stock.itemid
        left join model_masterfile as mm on mm.model_id = item.model
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
        left join client as warehouse on warehouse.clientid=stock.whid 
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join iteminfo as i on i.itemid  = item.itemid
        left join stockinfotrans as sit on sit.trno = stock.trno and sit.line=stock.line
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
        left join hstockinfotrans as sit on sit.trno = stock.trno and sit.line=stock.line
        where stock.trno =? order by sortline,line";

        $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);

        return $stock;
    } //end function openstock

    /**
     * this function is used to get the select part of the query for loading the line items in the grid. 
     * it will return the select part of the query which will be used in the openstock function to load the line items in the grid.
     */
    private function getstockselect($config)
    {
        $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);

        $sqlselect = "select item.brand as brand,
        ifnull(mm.model_name,'') as model,
        item.itemid,
        stock.trno,
        stock.line,
        stock.refx,
        stock.linex,
        item.barcode,
        if(ifnull(sit.itemdesc,'')='',item.itemname,sit.itemdesc) as itemname,
        stock.uom,
        stock.cost,
        '' as netamt,
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
        stock.rem,
        ifnull(uom.factor,1) as uomfactor,
        '' as bgcolor,
        case when stock.void=0 then '' else 'bg-red-2' end as errcolor,
        item.subcode, item.partno, round(item.dqty, " . $this->companysetup->getdecimal('qty', $config['params']) . ") as boxcount,
        concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',ifnull(i.itemdescription,'')) as itemdescription,
        1+1 as ordernum,stock.sortline
        ";
        return $sqlselect;
    } // end function getstockselect

    /**
     * this function is used to update the data in the header of the transaction. 
     * it will update the data in the header either in prhead or hprhead based on the transaction number passed. 
     * it will also sanitize the data before updating it in the database.
     */
    public function updatehead($config, $isupdate) // saves header changes (whether creating new or editing existing)
    {
        $head = $config['params']['head'];
        $companyid = $config['params']['companyid'];
        $data = [];
        if ($isupdate) {
            unset($this->fields[1]);
            unset($head['docno']);
        }
        $dateTables = ['prhead'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

        foreach ($this->fields as $key) {
            if (array_key_exists($key, $head)) {
                $data[$key] = $head[$key];
                if (!in_array($key, $this->except)) {
                    $data[$key] = $this->othersClass->sanitizekeyfieldFast($key, $data[$key], $lookups);
                } //end if
            }
        }
        // for existing records
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        if ($isupdate) {
            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
        } else {
            // new records
            $data['doc'] = $config['params']['doc'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $this->coreFunctions->sbcinsert($this->head, $data);
            $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
        }

        $infotransexist = $this->coreFunctions->getfieldvalue("headinfotrans", "trno", "trno=?", [$head['trno']]);
        if ($infotransexist == '') {
            $this->coreFunctions->sbcinsert("headinfotrans", ['trno' => $head['trno']]);
        }
    } // end function updatehead

    // Returns initial parameters for the document listing view
    public function paramsdatalisting($config)
    {
        return ['status' => true, 'data' => [], 'txtfield' => ['col1' => []]];
    }

    /**
     * this function is used to perform the actions in the stock status 
     * such as adding an item, deleting an item, updating an item, and other actions based on the parameters passed. 
     * Routes all item actions to the correct function
     */
    public function stockstatus($config)
    {
        switch ($config['params']['action']) {
            case 'additem':
                return $this->additem('insert', $config);
                break;
            case 'addallitem': // save all item selected from lookup
                return $this->addallitem($config);
                break;
            case 'quickadd': // quick add item
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
            case 'gettrsummary':
                return $this->gettrsummary($config);
                break;
            case 'gettrdetails':
                return $this->gettrdetails($config);
                break;
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
                break;
        }
    }

    /**
     * adds a new item OR updates an existing item line 
     * this function is used to add an item in the stock status based on the parameters passed such as itemid, uom, qty, cost, and other parameters. 
     * it will return the data of the item added to be displayed in the grid and also the message whether the item was successfully added or not.
     */
    public function additem($action, $config)
    {
        $uom = $config['params']['data']['uom'];
        $itemid = $config['params']['data']['itemid'];
        $trno = $config['params']['trno'];
        $disc = $config['params']['data']['disc'];
        $wh = $config['params']['data']['wh'];
        $loc = $config['params']['data']['loc'];
        $void = 'false';
        $companyid = $config['params']['companyid'];
        $itemdesc = '';
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
        $line = 0;
        if ($action == 'insert') {
            $qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";
            $line = $this->coreFunctions->datareader($qry, [$trno]);
            if ($line == '') {
                $line = 0;
            }
            $line = $line + 1; // increment line number for new item
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
        $dateTables = ['prstock'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);


        $amt = $this->othersClass->sanitizekeyfieldFast('amt', $amt, $lookups);
        $qty = $this->othersClass->sanitizekeyfieldFast('qty', $qty, $lookups);

        $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
        $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
        $factor = 1;
        if (!empty($item)) {
            $item[0]->factor = $this->othersClass->val($item[0]->factor);
            if ($item[0]->factor !== 0)
                $factor = $item[0]->factor;
        }
        $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));
        $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor);
        $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
        $data = [
            'trno' => $trno,
            'line' => $line,
            'itemid' => $itemid,
            'rrcost' => $amt, // original cost before discount
            'cost' => $computedata['amt'], // cost after discount
            'rrqty' => $qty, // original qty before conversion
            'qty' => $computedata['qty'], // qty after conversion
            'ext' => $computedata['ext'], // extended amount
            'disc' => $disc,
            'whid' => $whid,
            'loc' => $loc,
            'uom' => $uom,
            'void' => $void,
            'rem' => $rem
        ];
        foreach ($data as $key => $value) {
            $data[$key] = $this->othersClass->sanitizekeyfieldFast($key, $data[$key], $lookups);
        }
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $data['editdate'] = $current_timestamp;
        $data['editby'] = $config['params']['user'];
        if ($uom == '') {
            $msg = 'UOM cannot be blank -' . $item[0]->barcode;
            return ['status' => false, 'msg' => $msg];
        }

        if ($action == 'insert') {
            $data['sortline'] = $data['line'];
            $data['encodeddate'] = $current_timestamp;
            $data['encodedby'] = $config['params']['user'];
            // insert new item line in the stock table
            if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
                $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:' . $computedata['ext'] . ' Uom:' . $uom);
                $row = $this->openstockline($config);
                return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            } else {
                return ['status' => false, 'msg' => 'Add item Failed'];
            }
        } elseif ($action == 'update') { // update existing item line in the stock table
            return $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
        }
    } // end function

    /**
     * this function is used to load the data of a single line item based on the transaction number and line number passed. 
     * it will return the data of the line item to be displayed in the grid.
     */
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
        left join stockinfotrans as sit on sit.trno = stock.trno and sit.line=stock.line
        where stock.trno = ? and stock.line = ? ";

        $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);

        return $stock;
    } // end function

    /**
     * this function is used to add an item in the stock status based on the barcode passed. 
     * it will return the data of the item added to be displayed in the grid and also the message whether the item was successfully added or not.
     */
    public function quickadd($config)
    {
        $barcodelength = $this->companysetup->getbarcodelength($config['params']);
        $config['params']['barcode'] = trim($config['params']['barcode']);
        // if ($barcodelength == 0) {
        //   $barcode = $config['params']['barcode'];
        // } else {
        //   $barcode = $this->othersClass->padj($config['params']['barcode'], $barcodelength);
        // }
        $barcode = $config['params']['barcode'];
        $wh = $config['params']['wh'];
        // lookup item barcode
        $item = $this->coreFunctions->opentable("select item.itemid,0 as amt,item.disc,'' as loc,'" . $wh . "' as wh, 1 as qty, uom,famt from item where barcode=?", [$barcode]);
        $item = json_decode(json_encode($item), true);
        if (!empty($item)) {
            $config['params']['barcode'] = $barcode;
            // Get latest price from recent purchase orders
            $lprice = $this->getlatestprice($config);
            $lprice = json_decode(json_encode($lprice), true);
            if (!empty($lprice['data'])) {
                $item[0]['amt'] = $lprice['data'][0]['amt'];
                $item[0]['disc'] = $lprice['data'][0]['disc'];
            }
            $config['params']['data'] = $item[0];
            return $this->additem('insert', $config);
        } else {
            return ['status' => false, 'msg' => 'Barcode not found.' . $barcodelength, ''];
        }
    }

    /**
     * this function is used to get the latest purchase price of the item based on the barcode passed. 
     * it will return the latest purchase price of the item which will be used in the grid when adding a new item.
     */
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
    } // end function getlatestprice

    /**
     * Saves all edited items at once (batch update)
     * it will return the data of the line item to be displayed 
     * in the grid and also the message whether the item was successfully updated or not.
     */
    public function updateitem($config)
    {
        foreach ($config['params']['row'] as $key => $value) {
            $config['params']['data'] = $value;
            $this->additem('update', $config);
        }
        $data = $this->openstock($config['params']['trno'], $config);
        return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } //end function

    /**
     * Saves a single edited item (single line update)
     * it will return the data of the line item to be displayed 
     * in the grid and also the message whether the item was successfully updated or not.
     */
    public function updateperitem($config)
    {
        $config['params']['data'] = $config['params']['row'];
        $this->additem('update', $config);
        $data = $this->openstockline($config);
        return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    }

    // Removes one line item from the PR transaction.
    public function deleteitem($config)
    {
        $config['params']['trno'] = $config['params']['row']['trno'];
        $config['params']['line'] = $config['params']['row']['line'];
        // get the item being deleted (for logging)
        $data = $this->openstockline($config);

        $trno = $config['params']['trno'];
        $line = $config['params']['line'];
        // delete from stock table
        $qry = "delete from " . $this->stock . " where trno=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
        // delete from stockinfotrans for tracking purposes
        $this->coreFunctions->execqry('delete from stockinfotrans where trno=? and line=?', 'delete', [$trno, $line]);

        // log the deletion
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' barcode:' . $data[0]->barcode . ' Qty:' . $data[0]->rrqty . ' Amt:' . $data[0]->rrcost . ' Disc:' . $data[0]->disc . ' wh:' . $data[0]->wh . ' ext:' . $data[0]->ext);
        return ['status' => true, 'msg' => 'Item was successfully deleted.'];
    } // end function

    /**
     * Deletes all line items from the PR transaction. 
     * it will return the message whether all items were successfully deleted or not.
     */
    public function deleteallitem($config)
    {
        $isallow = true;
        $trno = $config['params']['trno'];
        // delete all lines for this PR
        $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
        // deleete all transaction infotrans for tracking purposes
        $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
        return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
    }

    /**
     * this function is used to add all items selected from the lookup in the stock status and save it in the stock table. 
     * it will return the data of all items added to be displayed in the grid and also the message whether all items were successfully added or not.
     */
    public function addallitem($config) // adding all item selected from lookup and save in the stock table
    {
        foreach ($config['params']['row'] as $key => $value) {
            $msg = 'Successfully saved.';
            $config['params']['data'] = $value;
            $return = $this->additem('insert', $config);
            if ($return['status'] == false) {
                $msg = $return['msg'];
                break; // stop if any item failed to add and return the message
            }
        }
        $data = $this->openstock($config['params']['trno'], $config);
        return ['inventory' => $data, 'status' => true, 'msg' => $msg];
    } //end function addallitem

    /**
     * this function is used to get the summary of the transaction based on the transaction number passed. 
     * it will return the data of all items in the transaction to be displayed in the grid and also the message whether the items were successfully added or not.
     */
    public function gettrsummary($config)
    {
        $trno = $config['params']['trno'];
        $wh = $config['params']['wh'];
        $rows = [];
        foreach ($config['params']['rows'] as $key => $value) {
            // get items from htrhead/htrstock where qty > qa (not yet allocated)
            $qry = "
        select head.docno, item.itemid,stock.trno, 
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-stock.qa) as qty,stock.rrcost,
        round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty, 
        stock.disc
        FROM htrhead as head left join htrstock as stock on stock.trno=head.trno left join item on item.itemid=
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
                    $config['params']['data']['loc'] = '';
                    $config['params']['data']['expiry'] = '';
                    $config['params']['data']['rem'] = '';
                    $config['params']['data']['refx'] = $data[$key2]->trno;
                    $config['params']['data']['linex'] = $data[$key2]->line;
                    $config['params']['data']['ref'] = $data[$key2]->docno;
                    $config['params']['data']['amt'] = $data[$key2]->rrcost;
                    $return = $this->additem('insert', $config);
                    if ($return['status']) {

                        array_push($rows, $return['row'][0]);
                    }
                } // end foreach
            } //end if
        } //end foreach
        return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
    } //end function

    /**
     * copy the items from the transaction summary to the transaction details.
     * it will return the data of all items in the transaction to be displayed in the grid and also the message whether the items were successfully added or not.
     */
    public function gettrdetails($config)
    {
        $trno = $config['params']['trno'];
        $wh = $config['params']['wh'];
        $rows = [];
        foreach ($config['params']['rows'] as $key => $value) {
            $qry = "
        select head.docno, item.itemid,stock.trno, 
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-stock.qa) as qty,stock.rrcost,
        round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty, 
        stock.disc
        FROM htrhead as head left join htrstock as stock on stock.trno=head.trno left join item on item.itemid=
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
                    $config['params']['data']['loc'] = '';
                    $config['params']['data']['expiry'] = '';
                    $config['params']['data']['rem'] = '';
                    $config['params']['data']['refx'] = $data[$key2]->trno;
                    $config['params']['data']['linex'] = $data[$key2]->line;
                    $config['params']['data']['ref'] = $data[$key2]->docno;
                    $config['params']['data']['amt'] = $data[$key2]->rrcost;
                    $return = $this->additem('insert', $config);
                    if ($return['status']) {

                        array_push($rows, $return['row'][0]);
                    }
                } // end foreach
            } //end if
        } //end foreach
        return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
    } //end functio

    /**
     * archives the PR and makes it read-only(final submission of the PR). 
     * it will move the data from prhead and prstock to hprhead and hprstock based on the transaction number passed. 
     * it will also check if there are items with zero quantity before posting and return an error message if there are any. 
     * it will return the message whether the transaction was successfully posted or not.
     */
    public function posttrans($config) // posting of transaction from prhead and prstock to hprhead and hprstock
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];

        // block posting if checkdate has not been set yet
        $checkdate = $this->coreFunctions->datareader("select checkdate as value from headinfotrans where trno=? limit 1", [$trno]);
        if (empty($checkdate)) {
            return ['status' => false, 'msg' => 'Posting failed. Transaction has not been tagged for approval yet.'];
        }

        // chech for zero qty items before posting
        $qry = "select trno from " . $this->stock . " where trno=? and qty=0 limit 1";
        $isitemzeroqty = $this->coreFunctions->opentable($qry, [$trno]);
        if (!empty($isitemzeroqty)) {
            return ['status' => false, 'msg' => 'Posting failed. Check carefully, some items have zero quantity.'];
        }
        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        // validate not already posted
        if ($this->othersClass->isposted($config)) {
            return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
        }
        // copy header from current to history table
        //for glhead
        $qry = "insert into " . $this->hhead . "(trno,doc,docno,client,clientname,address,shipto,dateid,
        terms,rem,forex,yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,agent,wh,due,cur,purtype,requestor, 
        budgetreqno)
        SELECT head.trno,head.doc, head.docno,head.client, head.clientname, head.address,head.shipto,
        head.dateid as dateid, head.terms, head.rem, head.forex,head.yourref, head.ourref,
        head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,head.agent,head.wh,
        head.due,head.cur,head.purtype,head.requestor,
        head.budgetreqno
        FROM " . $this->head . " as head left join cntnum on cntnum.trno=head.trno
        where head.trno=? limit 1";
        $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
        if ($posthead) {
            // post stock info transactions
            // for glstock
            if (!$this->othersClass->postingstockinfotrans($config)) {
                // rollback if stock allocation fails
                $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
                return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while posting stock/s.'];
            }

            // copy items current from current to history table
            $qry = "insert into " . $this->hstock . "(trno,line,itemid,uom,
                whid,loc,ref,disc,cost,qty,void,rrcost,rrqty,ext,
                encodeddate,qa,encodedby,editdate,editby,refx,linex,cdqa,rem,sortline)
                SELECT trno, line, itemid, uom,whid,loc,ref,disc,cost, qty,void,rrcost, rrqty, ext,
                encodeddate,qa, encodedby,editdate,editby,refx,linex,cdqa,rem,sortline FROM " . $this->stock . " where trno =?";
            if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
                //update transnum
                $date = $this->othersClass->getCurrentTimeStamp();
                $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
                // delete from current table (history is now the source)
                $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
                $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from stockinfotrans where trno=?", "delete", [$trno]);
                // log and transfer
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

    /**
     * reverses posting and moves the data back from hprhead 
     * and hprstock to prhead and prstock based on the transaction number passed. 
     */
    public function unposttrans($config) // unposting of transaction from hprhead and hprstock to prhead and prstock
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];
        // validate if anything was served/voided
        $qry = "select trno from " . $this->hstock . " where trno=? and (qa>0 or void<>0)";
        $data = $this->coreFunctions->opentable($qry, [$trno]);
        if (!empty($data)) {
            return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, either already served or have item voided...'];
        }
        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        // copy the header from history to current table
        $qry = "insert into " . $this->head . "(trno,doc,docno,client,clientname,address,shipto,dateid,terms,rem,forex,
        yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,wh,due,cur,purtype,requestor, budgetreqno)
        select head.trno, head.doc, head.docno, head.client, head.clientname, head.address, head.shipto,
        head.dateid as dateid, head.terms, head.rem, head.forex, head.yourref, head.ourref, head.createdate,
        head.createby, head.editby, head.editdate, head.lockdate, head.lockuser,head.wh,head.due,head.cur,head.purtype,head.requestor, head.budgetreqno
        from (" . $this->hhead . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno)
        where head.trno=? limit 1";

        $this->coreFunctions->LogConsole($qry);
        //head
        if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
            // unpost stock info transactions
            if (!$this->othersClass->unpostingstockinfotrans($config)) {
                $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
                return ['trno' => $trno, 'status' => false, 'msg' => 'Unposting failed. There are issues with inventory.'];
            }

            // copy items from history to current table
            $qry = "insert into " . $this->stock . "(
            trno,line,itemid,uom,whid,loc,ref,disc,
            cost,qty,void,rrcost,rrqty,ext,rem,encodeddate,qa,encodedby,editdate,editby,refx,linex,cdqa,sortline)
            select trno, line, itemid, uom,whid,loc,ref,disc,cost, qty,void, rrcost, rrqty,
            ext,rem, encodeddate, qa, encodedby, editdate, editby,refx,linex,cdqa,sortline
            from " . $this->hstock . " where trno=?";
            //stock
            if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
                // clear post information from transaction log
                $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
                // delete from history tables
                $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from hstockinfotrans where trno=?", "delete", [$trno]);
                $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
                return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
            } else {
                $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
                return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, stock problems...'];
            }
        } else {
            return ['status' => false, 'msg' => 'Error on Unposting Head'];
        }
    } //end function unposttrans

    /**
     * Completely removes a PR from the system (no recovery).
     * it will delete the data from prhead and prstock based on the transaction number passed
     */
    public function deletetrans($config) // deleting of transaction both in prhead and hprhead
    {
        $trno = $config['params']['trno'];
        $doc = $config['params']['doc'];
        $table = $config['docmodule']->tablenum;
        // get the docno for logging
        $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
        // get the previous trno for logging purposes
        $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
        $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);

        // delete everything related to this PR
        $this->coreFunctions->execqry('delete from ' . $this->stock . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
        $this->othersClass->deleteattachments($config);
        // log the deletion
        $this->logger->sbcdel_log($trno, $config, $docno);
        return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
    } //end function deletetrans

    /**
     * Marks items as voided in a posted (archived) PR.
     * it will update the void field in hprstock based on the transaction number and line number passed.
     * it will return the message whether the items were successfully voided or not.
     */
    private function updateitemvoid($config)
    {
        $trno = $config['params']['trno'];
        $rows = $config['params']['rows'];
        foreach ($rows as $key) {
            $this->coreFunctions->execqry('update ' . $this->hstock . ' set void=1 where trno=? and line=?', 'update', [$key['trno'], $key['line']]);
        }
    } //end function

    /**
     * routes actions that can only happen on POSTED transactions (hprhead and hprstock) to their respective functions.
     * it will check the action passed and call the respective function to perform the action on the posted transaction. 
     * it will return the message whether the action was successfully performed or not.
     */
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
            case 'forapproval':
                return $this->forapproval($config);
                break;
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
                break;
        }
    }

    public function forapproval($config)
    {
        $currentdate = $this->othersClass->getCurrentTimeStamp();
        $posted = $this->othersClass->isposted($config);
        if ($posted) {
            return ['status' => false, 'msg' => 'Already posted'];
        }

        $lockdate = $this->coreFunctions->datareader("select lockdate as value from prhead where trno=? limit 1", [$config['params']['trno']]);
        if (is_null($lockdate)) {
            return ['status' => false, 'msg' => 'Cannot tag for approval: lock date is not set'];
        }

        if ($this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 10], ['trno' => $config['params']['trno']])) {
            $this->coreFunctions->sbcupdate('headinfotrans', ['checkdate' => $currentdate], ['trno' => $config['params']['trno']]);
            $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', 'Tag FOR APPROVAL');
            return ['status' => true, 'msg' => 'Successfully updated.', 'backlisting' => true];
        } else {
            return ['status' => false, 'msg' => 'Failed to tag for approval'];
        }
    }

    // sets up the report configuration with filter fields
    public function reportsetup($config)
    {
        // Create report filter fields
        $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
        // Get report parameter data (default values, lookups, etc)
        $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

        $modulename = $this->modulename;
        $data = [];
        $style = 'width:500px;max-width:500px;';
        return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
    }

    // Generates the actual PR report (PDF/HTML) with data
    public function reportdata($config)
    {
        // log the report view
        $this->logger->sbcviewreportlog($config);

        $dataparams = $config['params']['dataparams'];
        if (isset($dataparams['received']))
            $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);
        if (isset($dataparams['approved']))
            $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
        if (isset($dataparams['prepared']))
            $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);

        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }

    /**
     * this function is used to get the summary of the PO transactions related to the PR based on the transaction number passed. 
     * it will return the data of all items in the related PO transactions to be displayed in the grid and also the message whether the items were successfully retrieved or not.
     */
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

    /*Gets a query for items that need Job Orders (JO) from this PR based on the transaction number passed. 
     * it will return the data of all items that need JO in the PR to be displayed in the grid and also the message whether the items were successfully retrieved or not.
     */
    public function getjosummaryqry($config)
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
    where head.trno = ? and stock.qty>(stock.qa+stock.cdqa) and item.islabor=1 and stock.void=0 order by sortline,linex ";
    }

    /*Generates a diagram showing the relationship between the PR and related transactions such as PO and RR based on the transaction number passed. 
     * it will return the data of all related transactions to be displayed in the diagram and also the message whether the diagram was successfully generated or not.
     */
    public function diagram($config)
    {
        $data = [];
        $nodes = []; // document boxes
        $links = []; // connection between boxes
        $data['width'] = 1500;
        $startx = 100;

        // finds the POs that reference this PR
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

            // add PO nodes and links to the diagram
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
                $a = $a + 100; // spacing

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
            //add RR nodes and links to the diagram
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
                    // add APV nodes and links to the diagram
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
                    // add CV nodes and links to the diagram
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
                    // add DM nodes and links to the diagram (for returns/adjustments)
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

        // return diagram data
        $data['nodes'] = $nodes; // all the boxes with positions
        $data['links'] = $links; // all connections

        return ['status' => true, 'msg' => 'Successfully fetched.', 'data' => $data];
    }
}
