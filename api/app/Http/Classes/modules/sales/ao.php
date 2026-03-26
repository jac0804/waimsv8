<?php

namespace App\Http\Classes\modules\sales;

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

class ao
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'SERVICE SALES ORDER';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    public $expirystatus = ['readonly' => true, 'show' => true, 'showdate' => false];
    public $tablenum = 'transnum';
    public $head = 'sshead';
    public $hhead = 'hsshead';
    public $stock = 'srstock';
    public $hstock = 'hsrstock';
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';
    public $htablelogs = 'htransnum_log';
    private $stockselect;
    public $dqty = 'isqty';
    public $hqty = 'iss';
    public $damt = 'isamt';
    public $hamt = 'amt';
    public $fields = ['trno', 'docno', 'dateid', 'delcharge'];
    public $except = ['trno', 'dateid'];
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;
    public $showfilterlabel = [
        ['val' => 'draft', 'label' => 'Draft', 'color' => 'Primary'],
        ['val' => 'pending', 'label' => 'Pending', 'color' => 'primary'],
        ['val' => 'locked', 'label' => 'Locked', 'color' => 'Primary'],
        ['val' => 'posted', 'label' => 'Posted', 'color' => 'Primary'],
        ['val' => 'all', 'label' => 'All', 'color' => 'Primary'],
    ];
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
            'view' => 2660,
            'edit' => 2661,
            'new' => 2662,
            'save' => 2663,
            // 'change' => 2664, remove change doc
            'delete' => 2665,
            'print' => 2666,
            'lock' => 2667,
            'unlock' => 2668,
            'post' => 2669,
            'unpost' => 2670,
            'deleteitem' => 3720
        );
        return $attrib;
    }


    public function createdoclisting($config)
    {
        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'yourref', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[5]['label'] = 'Customer PO';
        return $cols;
    }

    public function paramsdatalisting($config)
    {
        $fields = [];
        $companyid = $config['params']['companyid'];
        switch ($companyid) {
            case 10: //afti
            case 12: //afti usd
                $fields = ['selectprefix', 'docno'];
                $col1 = $this->fieldClass->create($fields);
                data_set($col1, 'docno.type', 'input');
                data_set($col1, 'docno.label', 'Search');
                data_set($col1, 'selectprefix.label', 'Search by');
                data_set($col1, 'selectprefix.type', 'lookup');
                data_set($col1, 'selectprefix.lookupclass', 'lookupsearchby');
                data_set($col1, 'selectprefix.action', 'lookupsearchby');
                $data = $this->coreFunctions->opentable("select '' as docno,'' as selectprefix");
                return ['status' => true, 'data' => $data[0], 'txtfield' => ['col1' => $col1]];
                break;
            default:
                return ['status' => true, 'data' => [], 'txtfield' => ['col1' => []]];
                break;
        }
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

        $join = '';
        $hjoin = '';
        $addparams = '';

        $leftjoin = "";
        $leftjoin_posted = "";
        switch ($itemfilter) {
            case 'draft':
                $condition = ' and num.postdate is null ';
                break;
            case 'pending':
                $leftjoin = ' left join hsrstock as stock on stock.trno = qt.trno';
                $leftjoin_posted = ' left join hsrstock as stock on stock.trno = qt.trno';
                $condition = ' and stock.iss>stock.qa';
                break;
            case 'posted':
                $condition = ' and num.postdate is not null ';
                break;
            case 'locked':
                $condition = ' and head.lockdate is not null ';
                break;
        }
        $companyid = $config['params']['companyid'];
        switch ($companyid) {
            case 10: //afti
            case 12: //afti usd
                $dateid = "date_format(head.dateid,'%m-%d-%Y') as dateid";
                if ($searchfilter == "") $limit = 'limit 50';
                break;
            default:
                $dateid = "left(head.dateid,10) as dateid";
                if ($searchfilter == "") $limit = 'limit 150';
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
                        $join = " left join srstock on srstock.trno = qt.trno
                  left join item on item.itemid = srstock.itemid left join item as item2 on item2.itemid = srstock.itemid
                  left join model_masterfile as model on model.model_id = item.model 
                  left join model_masterfile as model2 on model2.model_id = item2.model 
                  left join frontend_ebrands as brand on brand.brandid = item.brand 
                  left join frontend_ebrands as brand2 on brand2.brandid = item2.brand
                  left join projectmasterfile as p on p.line = item.projectid 
                  left join projectmasterfile as p2 on p2.line = item2.projectid ";

                        $hjoin = " left join hsrstock on hsrstock.trno = qt.trno
                  left join item on item.itemid = hsrstock.itemid left join item as item2 on item2.itemid = hsrstock.itemid
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

        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['head.docno', 'qt.clientname', 'head.createby', 'head.editby', 'head.viewby', 'num.postedby', 'qt.yourref'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        } else {
            $limit = 'limit 25';
        }

        $qry = "select head.trno,head.docno,qt.clientname,$dateid, 'DRAFT' as status,head.createby,head.editby,head.viewby,num.postedby ,qt.yourref 
        from " . $this->head . " as head 
        left join " . $this->tablenum . " as num on num.trno=head.trno 
        left join hsrhead as qt on qt.sotrno=head.trno
        " . $leftjoin . "
        " . $join . "
        where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " $filtersearch
        group by head.trno, head.docno, qt.clientname, head.dateid, status, head.createby, head.editby, head.viewby, num.postedby,qt.yourref
        union all
        select head.trno,head.docno,qt.clientname,$dateid,'POSTED' as status,head.createby,head.editby,head.viewby, num.postedby  ,qt.yourref
        from " . $this->hhead . " as head 
        left join " . $this->tablenum . " as num on num.trno=head.trno 
        left join hsrhead as qt on qt.sotrno=head.trno
        " . $leftjoin_posted . "
        " . $hjoin . "
        where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " $filtersearch
        group by head.trno, head.docno, qt.clientname, head.dateid, status, head.createby, head.editby, head.viewby, num.postedby ,qt.yourref
        order by dateid desc,docno desc $limit";

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

        if ($this->companysetup->getisshowmanual($config['params'])) {
            $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => $config['params']['doc'], 'title' => strtoupper($this->modulename) . '_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
        }

        return $buttons;
    } // createHeadbutton

    public function createtab2($access, $config)
    {
        $companyid = $config['params']['companyid'];
        if ($companyid == 10 || $companyid == 12) { //afti, afti usd
            $billshipdefault = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewbillingshipping']];
            $termstaxandcharges = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewtermstaxcharges']];
            $instructiontab = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewinstructiontab']];
            $viewleadtimesetting = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewleadtimesetting']];

            $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrysqcomment', 'label' => 'Comments']];
            $comments = $this->tabClass->createtab($tab, []);

            $return['SHIPPING/BILLING ADDRESS'] = ['icon' => 'fa fa-map-marker-alt', 'customform' => $billshipdefault];
            $return['INSTRUCTION'] = ['icon' => 'fa fa-info', 'customform' => $instructiontab];
            $return['LEAD TIME DURATION'] = ['icon' => 'fa fa-clock', 'customform' => $viewleadtimesetting];
            $return['TERMS, TAXES AND CHARGES'] = ['icon' => 'fa fa-file-invoice', 'customform' => $termstaxandcharges];
            $return['COMMENTS'] = ['icon' => 'fa fa-comment', 'tab' => $comments];
        }
        $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];
        $obj = $this->tabClass->createtab($tab, []);

        $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

        return $return;
    }


    public function createTab($access, $config)
    {
        $sq_makepo = $this->othersClass->checkAccess($config['params']['user'], 2599);
        $deliverydate = $this->othersClass->checkAccess($config['params']['user'], 2874);
        $companyid = $config['params']['companyid'];
        $action = 0;
        $itemdesc = 1;
        $isqty = 2;
        $uom = 3;
        $isamt = 4;
        $disc = 5;
        $ext = 6;
        $insurance = 7;
        $wh = 8;
        $whname = 9;
        $qa = 10;
        $void = 11;
        $ref = 12;
        $itemname = 13;
        $barcode = 14;
        $stock_projectname = 15;

        $gridcolumn = ['action', 'itemdescription', 'isqty', 'uom', 'isamt', 'disc', 'ext', 'insurance', 'wh', 'whname', 'qa', 'void', 'ref', 'itemname', 'barcode', 'stock_projectname'];

        $headgridbtns = ['viewref', 'viewitemstockinfo', 'viewdiagram'];

        if ($deliverydate != 0) {
            array_push($headgridbtns, 'viewdeliverydate');
        }

        if ($sq_makepo != 0) {
            array_push($headgridbtns, 'makejo');
        }

        $tab = [
            $this->gridname => [
                'gridcolumns' => $gridcolumn,
                'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'], 'headgridbtns' => $headgridbtns
            ],
        ];

        $stockbuttons = ['showbalance'];
        switch ($this->companysetup->getsystemtype($config['params'])) {
            case 'AIMS':
                if ($companyid == 0) { //main
                    array_push($stockbuttons, 'stockinfo');
                } else if ($companyid == 10 || $companyid == 12) { //afti, afti usd
                    array_push($stockbuttons, 'iteminfo');
                }
                break;
        }

        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0]['inventory']['columns'][$wh]['type'] = 'label';
        $obj[0]['inventory']['columns'][$uom]['type'] = 'label';
        $obj[0]['inventory']['columns'][$disc]['type'] = 'label';
        $obj[0]['inventory']['columns'][$isamt]['type'] = 'label';
        $obj[0]['inventory']['columns'][$isqty]['type'] = 'label';
        $obj[0]['inventory']['columns'][$ref]['type'] = 'label';
        $obj[0]['inventory']['columns'][$insurance]['type'] = 'label';

        $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
        $obj[0]['inventory']['columns'][$barcode]['label'] = '';

        $obj[0]['inventory']['columns'][$void]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$qa]['type'] = 'coldel';

        $obj[0]['inventory']['columns'][$isqty]['style'] = 'text-align:right;width:80px';

        $obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'label';

        if ($companyid == 10 || $companyid == 12) { //afti, afti usd
            $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'textarea';
            $obj[0]['inventory']['columns'][$itemdesc]['readonly'] = true;
            $obj[0]['inventory']['columns'][$itemdesc]['style'] = 'text-align: left; width: 350px;whiteSpace: normal;min-width:350px;max-width:350px;';
            $obj[0]['inventory']['columns'][$wh]['type'] = 'coldel';
            $obj[0]['inventory']['columns'][$whname]['type'] = 'label';
        }

        switch ($companyid) {
            case 10: //afti
            case 12: //afti usd
                break;
            default:
                $obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'coldel';
                $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'coldel';
                $obj[0]['inventory']['columns'][$whname]['type'] = 'coldel';
                break;
        }

        $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);

        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['unlinksq'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function createHeadField($config)
    {
        $companyid = $config['params']['companyid'];
        $fields = ['docno', 'client', 'clientname'];
        if ($companyid != 10 && $companyid != 12) { //not afti & not afti usd
            array_push($fields, 'address');
        }
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.lookupclass', 'srcustomer');
        data_set($col1, 'client.condition', ['checkstock']);
        data_set($col1, 'docno.label', 'Transaction#');
        data_set($col1, 'clientname.class', 'sbccsreadonly');
        data_set($col1, 'address.class', 'sbccsreadonly');

        if ($companyid == 10 || $companyid == 12) { //afti, afti usd
            data_set($col1, 'clientname.type', 'textarea');
            data_set($col1, 'businesstype.type', 'textarea');
        }

        $fields = [['dateid', 'terms'], ['qtdateid', 'due'], 'dwhname', 'dagentname'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'due.class', 'sbccsreadonly');
        data_set($col2, 'terms.class', 'sbccsreadonly');
        data_set($col2, 'qtdateid.class', 'sbccsreadonly');
        data_set($col2, 'due.class', 'sbccsreadonly');
        data_set($col2, 'dwhname.class', 'sbccsreadonly');
        data_set($col2, 'dagentname.class', 'sbccsreadonly');

        data_set($col2, 'terms.type', 'input');
        data_set($col2, 'dwhname.type', 'input');
        data_set($col2, 'dagentname.type', 'input');

        $fields = ['dbranchname', 'ddeptname', 'yourref', ['cur', 'forex']];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'yourref.class', 'sbccsreadonly');
        data_set($col3, 'ourref.class', 'sbccsreadonly');
        data_set($col3, 'cur.class', 'sbccsreadonly');
        data_set($col3, 'forex.class', 'sbccsreadonly');
        data_set($col3, 'dbranchname.required', true);
        data_set($col3, 'ddeptname.label', 'Department');
        if ($companyid == 10 || $companyid == 12) { //afti, afti usd
            data_set($col3, 'yourref.label', 'Customer PO');
        } else {
            data_set($col3, 'yourref.label', 'PO#');
        }
        data_set($col3, 'cur.type', 'input');

        $fields = ['rem', ['lbltotal', 'ext'], ['lbltaxes', 'taxesandcharge'], ['lblgrandtotal', 'totalcash']];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'rem.required', false);
        data_set($col4, 'rem.class', 'sbccsreadonly');
        data_set($col4, 'ext.class', 'sbccsreadonly');
        data_set($col4, 'ext.label', '');
        data_set($col4, 'taxesandcharge.label', '');
        data_set($col4, 'taxesandcharge.class', 'sbccsreadonly');
        data_set($col4, 'totalcash.label', '');

        return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
    }

    public function createnewtransaction($docno, $params)
    {
        $data = [];
        $data[0]['trno'] = 0;
        $data[0]['qtrno'] = 0;
        $data[0]['docno'] = $docno;
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['due'] = $this->othersClass->getCurrentDate();
        $data[0]['qtdateid'] = $this->othersClass->getCurrentDate();
        $data[0]['client'] = '';
        $data[0]['clientname'] = '';
        $data[0]['yourref'] = '';
        $data[0]['shipto'] = '';
        $data[0]['ourref'] = '';
        $data[0]['rem'] = '';
        $data[0]['agent'] = '';
        $data[0]['agentname'] = '';
        $data[0]['dagentname'] = '';
        $data[0]['branchcode'] = '';
        $data[0]['branchname'] = '';
        $data[0]['dbranchname'] = '';
        $data[0]['ddeptname'] = '';
        $data[0]['deptid'] = '0';
        $data[0]['dept'] = '';
        $data[0]['branch'] = 0;
        $data[0]['terms'] = '';
        $data[0]['forex'] = 1;
        $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
        $data[0]['address'] = '';
        $data[0]['wh'] = $this->companysetup->getwh($params);
        $name = $this->coreFunctions->datareader("select clientname as value from client where client='" . $data[0]['wh'] . "'");
        $data[0]['whname'] = $name;
        $data[0]['delcharge'] = 0;
        return $data;
    }

    public function loadheaddata($config)
    {
        $doc = $config['params']['doc'];
        $center = $config['params']['center'];
        $trno = $config['params']['trno'];
        $companyid = $config['params']['companyid'];
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
        $islocked = $this->othersClass->islocked($config);
        $isposted = $this->othersClass->isposted($config);
        $table = $this->head;
        $htable = $this->hhead;
        $tablenum = $this->tablenum;
        $qryselect = "select 
         num.center,
         head.trno, 
         head.docno,
         client.client,
         qt.tax,
         qt.terms,
         qt.cur,
         qt.forex,
         qt.yourref,
         qt.ourref,
         left(head.dateid,10) as dateid, 
         left(qt.dateid,10) as qtdateid, 
         qt.clientname,
         qt.address, 
         qt.shipto, 
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         qt.rem,
         ifnull(qt.agent, '') as agent, 
         ifnull(agent.clientname, '') as agentname,'' as dagentname,
         qt.wh as wh,
         warehouse.clientname as whname,
         '' as dwhname, 
         left(qt.due,10) as due, 
         client.groupid, qt.trno as qtrno,ifnull(b.client,'') as branchcode ,ifnull(b.clientname,'') as branchname, qt.branch,'' as dbranchname,
         ifnull(d.client,'') as dept,ifnull(d.clientname,'') as deptname,qt.deptid,'' as ddeptname,head.delcharge,qt.trno as srtrno,qt.qtrno as qttrno ";

        $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join hsrhead as qt on qt.sotrno=head.trno
        left join client on qt.client = client.client
        left join client as warehouse on warehouse.client = qt.wh
        left join client as agent on agent.client = qt.agent
        left join client as b on b.clientid = qt.branch
        left join client as d on d.clientid = qt.deptid
        where head.trno = ? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join hsrhead as qt on qt.sotrno=head.trno
        left join client on qt.client = client.client
        left join client as warehouse on warehouse.client = qt.wh
        left join client as agent on agent.client = qt.agent
        left join client as b on b.clientid = qt.branch
        left join client as d on d.clientid = qt.deptid where head.trno = ? and num.center=? ";

        $head = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);

        if (!empty($head)) {
            $stock = $this->openstock($head[0]->qtrno, $config);
            $viewdate = $this->othersClass->getCurrentTimeStamp();
            $viewby = $config['params']['user'];
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }
            $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);

            if ($companyid == 10 || $companyid == 12) { //afti, afti usd
                $sqry = "select sum(ext) as value from $this->hstock as stock 
                    left join item on item.itemid=stock.itemid 
                    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
                    left join client as warehouse on warehouse.clientid=stock.whid
                    left join hqshead as head on head.trno=stock.trno 
                    left join projectmasterfile as prj on prj.line = stock.projectid
                    where stock.trno =? ";
                if ($head[0]->tax == '12') {
                    $ext = round($this->coreFunctions->datareader($sqry, [$head[0]->qtrno]), 2);

                    $tax = $charges = 0;
                    $charges = $ext * .12;
                    $tax = round($ext - $charges, 2);
                    $amount = $ext + $charges;
                    $taxdef = round($this->coreFunctions->datareader("select taxdef as value from hheadinfotrans where trno = ?", [$head[0]->qttrno]), 2);

                    if ($taxdef != 0) {
                        $charges = $taxdef;
                        $amount = $ext + $charges;
                    }

                    $head[0]->ext = number_format($ext, $this->companysetup->getdecimal('default', $config['params']));
                    $head[0]->taxesandcharge = number_format($charges, $this->companysetup->getdecimal('default', $config['params']));
                    $head[0]->totalcash = number_format($amount, 2);
                } else {
                    $ext = round($this->coreFunctions->datareader($sqry, [$head[0]->qtrno]), 2);

                    $tax = $charges = 0;
                    $charges = 0;
                    $tax = 0;
                    $amount = $ext + $charges;
                    $taxdef = round($this->coreFunctions->datareader("select taxdef as value from hheadinfotrans where trno = ?", [$head[0]->qttrno]), 2);

                    if ($taxdef != 0) {
                        $charges = $taxdef;
                        $amount = $ext + $charges;
                    }

                    $head[0]->ext = number_format($ext, $this->companysetup->getdecimal('price', $config['params']));
                    $head[0]->taxesandcharge = number_format($charges, $this->companysetup->getdecimal('price', $config['params']));
                    $head[0]->totalcash = number_format($amount, 2);
                }
            }

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

        if ($isupdate) {
            $update  = $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
            if ($update) {
                $this->coreFunctions->sbcupdate('hsrhead', ['sotrno' => $head['trno']], ['trno' => $head['srtrno']]);
            }
        } else {
            $data['doc'] = $config['params']['doc'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];

            if ($this->coreFunctions->sbcinsert($this->head, $data)) {
                $this->coreFunctions->sbcupdate('hsrhead', ['sotrno' => $head['trno']], ['trno' => $head['srtrno']]);

                //updating to attendee
                $qtrno = $this->coreFunctions->getfieldvalue("hsrhead", "qtrno", "sotrno=?", [$head['trno']]);
                if($qtrno !=0){
                    $exist = $this->coreFunctions->getfieldvalue("attendee", "line", "optrno=?", [$qtrno]);
                    if (floatval($exist) != 0) {
                        $this->coreFunctions->execqry("update attendee set optrno = ? where optrno =?", 'update', [$head['trno'], $qtrno]);
                    } else {
                        $sotrno = $this->coreFunctions->getfieldvalue("hqshead", "sotrno", "trno=?", [$qtrno]);
                        if (floatval($sotrno) != 0) {
                            $this->coreFunctions->execqry("update attendee set optrno = ? where optrno =?", 'update', [$head['trno'], $sotrno]);
                        }
                    }  
                }
                

                $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
            }
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

        $qtrno = $this->coreFunctions->getfieldvalue("hsrhead", "qtrno", "sotrno=?", [$trno]);
        $exist = $this->coreFunctions->getfieldvalue("attendee", "line", "optrno=?", [$trno]);

        $this->coreFunctions->execqry('update hsrhead set sotrno=0 where sotrno=?', 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);

        if (floatval($exist) != 0) {
            if($qtrno !=0){
                $sotrno = $this->coreFunctions->getfieldvalue("hqshead", "sotrno", "trno=?", [$qtrno]);
                if (floatval($sotrno) != 0) {
                    $this->coreFunctions->execqry("update attendee set optrno = ? where optrno =?", 'update', [$sotrno, $trno]);
                } else {
                    $this->coreFunctions->execqry("update attendee set optrno = ? where optrno =?", 'update', [$qtrno, $trno]);
                }
            }
            
        }

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

        if (!empty($isitemzeroqty)) {
            return ['status' => false, 'msg' => 'Posting failed. Check carefully, some items have zero quantity.'];
        }
        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        if ($this->othersClass->isposted($config)) {
            return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
        }
        //for glhead
        $qry = "insert into " . $this->hhead . " (trno, doc, docno, dateid, voiddate, approvedby, approveddate, printtime, lockuser, lockdate, openby, users, createdate, createby, editby, editdate, viewby, viewdate,delcharge)
            SELECT trno, doc, docno, dateid, voiddate, approvedby, approveddate, printtime, lockuser, lockdate, openby, users, createdate, createby, editby, editdate, viewby, viewdate,delcharge FROM " . $this->head . " where trno=? limit 1";
        $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
        if ($posthead) {

            $date = $this->othersClass->getCurrentTimeStamp();
            $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
            $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
            $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
            $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
            $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
            return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
        } else {
            return ['status' => false, 'msg' => 'Error on Posting Head'];
        }
    } //end function

    public function unposttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];
        $sr_trno = $this->coreFunctions->datareader("select trno as value from hsrhead where sotrno = ? LIMIT 1", [$trno]);

        $checking = $this->coreFunctions->opentable("select trno from lastock where refx = ?
          union all
          select trno from glstock where refx = ?", [$sr_trno, $sr_trno]);

        if (!empty($checking)) {
            return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, either already have BS...'];
        }

        $qry = "select trno from " . $this->hstock . " where trno=? and ((qa+sjqa+voidqty)>0 or void<>0)";
        $data = $this->coreFunctions->opentable($qry, [$sr_trno]);
        if (!empty($data)) {
            return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, either already served or have item voided...'];
        }

        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        $qry = "insert into " . $this->head . "(trno, doc, docno, dateid, voiddate, approvedby, approveddate, printtime, lockuser, lockdate, openby, users, createdate, createby, editby, editdate, viewby, viewdate,delcharge)
                select trno, doc, docno, dateid, voiddate, approvedby, approveddate, printtime, lockuser, lockdate, openby, users, createdate, createby, editby, editdate, viewby, viewdate,delcharge from " . $this->hhead . " where trno=? limit 1";
        //head
        if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {

            $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
            $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
            $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
            return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
        } else {
            return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, head problems...'];
        }
    } //end function

    private function getstockselect($config)
    {
        $companyid = $config['params']['companyid'];
        $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);
        if ($companyid == 10 || $companyid == 12) { //afti, afti usd
            $qty_dec = 0;
        }
        $sqlselect = "select 
    item.itemid,
    stock.trno, 
    stock.line,
    item.barcode, 
    item.itemname,
    concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',ifnull(i.itemdescription,'')) as itemdescription,
    stock.uom, 
    stock.iss,
    FORMAT(stock.isamt," . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt,
    FORMAT(stock.isqty," . $qty_dec . ")  as isqty,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext, 
    left(stock.encodeddate,10) as encodeddate,
    stock.disc, 
    case when stock.void=0 then 'false' else 'true' end as void,
    round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
    stock.whid,
    warehouse.client as wh,
    warehouse.clientname as whname,
    stock.loc,stock.expiry,
    item.brand,
    stock.rem, 
    head.docno as ref,
    ifnull(uom.factor,1) as uomfactor,
    '' as bgcolor,
    case when stock.void=0 then '' else 'bg-red-2' end as errcolor,case when (stock.qa+stock.voidqty)<>stock.iss and stock.void<>1 then 'bg-orange-2' else '' end as qacolor,
    prj.name as stock_projectname,
    stock.projectid as projectid,stock.sgdrate,stock.insurance ";
        return $sqlselect;
    }

    public function openstock($trno, $config)
    {
        $sqlselect = $this->getstockselect($config);

        $qry = $sqlselect . "  
        FROM $this->hstock as stock 
        left join item on item.itemid=stock.itemid 
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
        left join client as warehouse on warehouse.clientid=stock.whid
        left join hsrhead as head on head.trno=stock.trno 
        left join sshead as so on so.trno = head.sotrno
        left join projectmasterfile as prj on prj.line = stock.projectid
        left join model_masterfile as mm on mm.model_id = item.model
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join iteminfo as i on i.itemid  = item.itemid
        where stock.trno =? order by line ";
        $stock = $this->coreFunctions->opentable($qry, [$trno]);
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
        left join hsrhead as head on head.trno=stock.trno 
        left join hsshead as so on so.trno = head.sotrno
        left join projectmasterfile as prj on prj.line = stock.projectid
        left join model_masterfile as mm on mm.model_id = item.model
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join iteminfo as i on i.itemid  = item.itemid
        where so.trno = ? and stock.line = ? 
       ";
        $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
        return $stock;
    } // end function

    public function stockstatus($config)
    {
        switch ($config['params']['action']) {
            case 'createversion':
                $return = $this->posttrans($config);
                if ($return['status']) {
                    return $this->othersClass->createversion($config);
                } else {
                    return $return;
                }
                break;
            case 'additem':
                $return =  $this->additem('insert', $config);

                return $return;
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
        switch ($config['params']['action']) {
            case 'diagram':
                return $this->diagram($config);
                break;
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
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
          from hsshead as so
          left join hsrhead as hsrhead on hsrhead.sotrno = so.trno
          left join hsrstock as s on s.trno = hsrhead.trno
          where so.trno = ?
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

        //SC
        $qry = "
          select schead.trno,schead.docno,left(schead.dateid,10) as dateid,
          CAST(concat('Total SC Amt: ',round(sum(s.ext),2)) as CHAR) as rem
          from hsrhead as schead
          left join hsrstock as s on s.trno = schead.trno
          where schead.sotrno = ?
          group by schead.trno,schead.docno,schead.dateid";
        $t = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno']]);
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
                // SI
                $dmqry = "
            select head.docno as docno,left(head.dateid,10) as dateid,
            CAST(concat('Total SI Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem 
            from glhead as head
            left join glstock as stock on stock.trno=head.trno 
            left join item on item.itemid = stock.itemid
            where stock.refx=?
            group by head.docno, head.dateid
            union all
            select head.docno as docno,left(head.dateid,10) as dateid,
            CAST(concat('Total SI Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem 
            from lahead as head
            left join lastock as stock on stock.trno=head.trno 
            left join item on item.itemid=stock.itemid
            where stock.refx=?
            group by head.docno, head.dateid";
                $dmdata = $this->coreFunctions->opentable($dmqry, [$t[$key]->trno, $t[$key]->trno]);
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
                        array_push($links, ['from' => 'sj', 'to' => $dmdata[$key2]->docno]);
                        $a = $a + 100;
                    }
                }
            }
        }

        $data['nodes'] = $nodes;
        $data['links'] = $links;

        return ['status' => true, 'msg' => 'Successfully fetched.', 'data' => $data];
    }

    public function updateperitem($config)
    {
        $config['params']['data'] = $config['params']['row'];
        $this->additem('update', $config);
        $data = $this->openstockline($config);
        return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
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
            $config['params']['data'] = $value;
            $this->additem('insert', $config);
        }

        $data = $this->openstock($config['params']['trno'], $config);
        return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
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

        $item = $this->coreFunctions->opentable("select item.itemid,item.amt,item.disc,'' as loc,'" . $wh . "' as wh, 1 as qty, uom from item where barcode=?", [$barcode]);
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
        $uom = $config['params']['data']['uom'];
        $itemid = $config['params']['data']['itemid'];
        $trno = $config['params']['trno'];
        $disc = $config['params']['data']['disc'];
        $wh = $config['params']['data']['wh'];
        $loc = $config['params']['data']['loc'];
        $void = 'false';
        $rem = '';
        $expiry = '';

        if (isset($config['params']['data']['void'])) {
            $void = $config['params']['data']['void'];
        }

        if (isset($config['params']['data']['rem'])) {
            $rem = $config['params']['data']['rem'];
        }

        if (isset($config['params']['data']['expiry'])) {
            $expiry = $config['params']['data']['expiry'];
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
        $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
        $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
        $factor = 1;
        if (!empty($item)) {
            $item[0]->factor = $this->othersClass->val($item[0]->factor);
            if ($item[0]->factor !== 0) $factor = $item[0]->factor;
        }
        $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
        $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor);
        $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);

        if (floatval($forex) == 0) {
            $forex = 1;
        }

        $data = [
            'trno' => $trno,
            'line' => $line,
            'itemid' => $itemid,
            'isamt' => $amt,
            'amt' => $computedata['amt'] * $forex,
            'isqty' => $qty,
            'iss' => $computedata['qty'],
            'ext' => $computedata['ext'],
            'disc' => $disc,
            'whid' => $whid,
            'loc' => $loc,
            'void' => $void,
            'uom' => $uom,
            'rem' => $rem,
            'expiry' => $expiry
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
                $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:' . $computedata['ext']);
                $row = $this->openstockline($config);
                $this->loadheaddata($config);
                return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.', 'reloaddata' => true];
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
        $qtrno = $this->coreFunctions->getfieldvalue("hsrhead", "qtrno", "sotrno=?", [$trno]);
        $exist = $this->coreFunctions->getfieldvalue("attendee", "line", "optrno=?", [$trno]);
        $this->coreFunctions->execqry('update hsrhead set sotrno = 0 where sotrno = ?', 'update', [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED SR REFERENCE');

        if (floatval($exist) != 0) {
            if($qtrno !=0){
                $sotrno = $this->coreFunctions->getfieldvalue("hqshead", "sotrno", "trno=?", [$qtrno]);
                if (floatval($sotrno) != 0) {
                    $this->coreFunctions->execqry("update attendee set optrno = ? where optrno =?", 'update', [$sotrno, $trno]);
                } else {
                    $this->coreFunctions->execqry("update attendee set optrno = ? where optrno =?", 'update', [$qtrno, $trno]);
                }
            }
            
        }

        $this->loadheaddata($config);
        return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => [], 'reloadhead' => true];
    }


    public function deleteitem($config)
    {
        $config['params']['trno'] = $config['params']['row']['trno'];
        $config['params']['line'] = $config['params']['row']['line'];
        $data = $this->openstockline($config);
        //if(($data[0]->qa == $data[0]->qty)){
        $trno = $config['params']['trno'];
        $line = $config['params']['line'];
        $qry = "delete from " . $this->stock . " where trno=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' barcode:' . $data[0]->barcode . ' Qty:' . $data[0]->isqty . ' Amt:' . $data[0]->isamt . ' Disc:' . $data[0]->disc . ' wh:' . $data[0]->wh . ' ext:' . $data[0]->ext);
        return ['status' => true, 'msg' => 'Item was successfully deleted.'];
        //} else {
        //    return ['status'=>false,'msg'=>'Cannot delete, already served'];
        //}
    } // end function

    public function getlatestprice($config)
    {
        $barcode = $config['params']['barcode'];
        $client = $config['params']['client'];
        $center = $config['params']['center'];
        $trno = $config['params']['trno'];

        $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,disc,uom,moq,mmoq from(select head.docno,head.dateid,
          stock.isamt as amt,stock.uom,stock.disc,item.moq,item.mmoq
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid = stock.itemid
          where head.doc = 'SJ' and cntnum.center = ?
          and item.barcode = ? and head.client = ?
          and stock.isamt <> 0
          UNION ALL
          select head.docno,head.dateid,stock.isamt as amt,
          stock.uom,stock.disc,item.moq,item.mmoq from glhead as head
          left join glstock as stock on stock.trno = head.trno
          left join item on item.itemid = stock.itemid
          left join client on client.clientid = head.clientid
          left join cntnum on cntnum.trno=head.trno 
          where head.doc = 'SJ' and cntnum.center = ?
          and item.barcode = ? and client.client = ?
          and stock.isamt <> 0
          order by dateid desc limit 5) as tbl order by dateid desc limit 1";
        $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $client, $center, $barcode, $client]);

        $usdprice = 0;
        $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
        $cur = $this->coreFunctions->getfieldvalue($this->head, 'cur', 'trno=?', [$trno]);
        $dollarrate = $this->coreFunctions->getfieldvalue('forex_masterfile', 'dollartocur', 'cur=?', [$cur]);

        if (!empty($data)) {
            return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
        } else {
            $qry = "select amt,disc,uom,moq,mmoq from item where barcode=?";
            $data = $this->coreFunctions->opentable($qry, [$barcode]);
            if (floatval($forex) <> 1) {
                $usdprice = $this->coreFunctions->getfieldvalue('item', 'foramt', 'barcode=?', [$barcode]);
                if ($cur == '$') {
                    $data[0]->amt = $usdprice;
                } else {
                    $data[0]->amt = round($usdprice * $dollarrate, 2);
                }
            }


            if (floatval($data[0]->amt) == 0) {
                return ['status' => false, 'msg' => 'No Latest price found...'];
            } else {
                return ['status' => true, 'msg' => 'Found the latest price...', 'data' => $data];
            }
        }
    } // end function


    public function getposummaryqry($config)
    {
        $qry = "
            select concat(stock.trno,stock.line) as keyid, stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, stock.uom, head.docno, date(head.dateid) as dateid,
            (stock.iss-(stock.qa+stock.sjqa+stock.poqa)) as iss,stock.isamt, stock.disc,stock.amt,stock.ext,item.amt4 as tpphp,head.wh,head.branch,head.deptid,
            FORMAT(((stock.iss-(stock.qa+stock.sjqa+stock.poqa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,head.yourref,item.famt as tpdollar
            from  hsrhead as head left join hsrstock as stock on stock.trno=head.trno
            left join item on item.itemid=stock.itemid
            left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
            left join transnum on transnum.trno = head.trno
            where head.doc='SR' and stock.iss > stock.qa and stock.void = 0 and head.sotrno=? order by stock.line
          ";
        return $qry;
    }

    // report 
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
        if ($companyid == 10 || $companyid != 12) { //afti, not afti usd
        } else {
            $this->logger->sbcviewreportlog($config);
        }
        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }
} //end class
