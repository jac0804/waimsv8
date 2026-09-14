<?php

namespace App\Http\Classes\modules\e4dea67c8f0a09c517731ee700878f6cb;

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

class td
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Delivery Trucking';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    public $expirystatus = ['readonly' => true, 'show' => true, 'showdate' => false];
    public $tablenum = 'transnum';
    public $head = 'rohead';
    public $hhead = 'hrohead';
    public $stock = 'rostock';
    public $hstock = 'hrostock';
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';
    public $htablelogs = 'htransnum_log';
    private $stockselect;
    public $dqty = 'isqty';
    public $hqty = 'iss';
    public $damt = 'isamt';
    public $hamt = 'amt';
    public $fields = ['trno', 'docno', 'dateid', 'client', 'clientname', 'rem', 'address', 'tel', 'odoin', 'odoout', 'amt', 'checkedby'];
    public $fieldOthers = ['trno', 'truckid', 'plateno', 'helperid', 'loaddate'];
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
            'view' => 6016,
            'edit' => 6017,
            'new' => 6018,
            'save' => 6019,
            'delete' => 6020,
            'print' => 6021,
            'lock' => 6022,
            'unlock' => 6023,
            'post' => 6024,
            'unpost' => 6025,
            'additem' => 6026,
            'edititem' => 6027,
            'deleteitem' => 6028
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
        $listclientname = 4;
        $listpostedby = 5;
        $postdate = 6;
        $listcreateby = 7;
        $createdate = 8;
        $listeditby = 9;
        $listviewby = 10;

        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'listpostedby', 'postdate', 'listcreateby', 'createdate', 'listeditby', 'listviewby'];

        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $cols = $this->tabClass->delcollisting($cols);
        return $cols;
    }

    public function loaddoclisting($config)
    {

        $date1 = date('Y-m-d', strtotime($config['params']['date1']));
        $date2 = date('Y-m-d', strtotime($config['params']['date2']));
        $itemfilter = $config['params']['itemfilter'];
        $companyid = $config['params']['companyid'];
        $doc = $config['params']['doc'];
        $center = $config['params']['center'];
        $condition = '';
        $laext = '';
        $glext = '';

        $join = '';
        $hjoin = '';
        $addparams = '';

        $ustatus = "Pending";

        $orderby = "order by docno desc, dateid desc";
        $dateid = "left(head.dateid,10) as dateid";
        $status = "stat.status";
        $ustatus = "Unposted";
        $searchfilter = $config['params']['search'];

        $leftjoin = "";
        $leftjoin_posted = "";
        switch ($itemfilter) {
            case 'draft':
                $condition = ' and num.postdate is null and head.lockdate is null ';
                $ustatus = "Unposted";
                break;

            case 'locked':
                $condition = ' and head.lockdate is not null and num.postdate is null ';
                $ustatus = "Locked";
                break;

            case 'posted':
                $condition = ' and num.postdate is not null ';
                $ustatus = "Posted";
                break;
        }

        $limit = "limit 150";
        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['head.docno', 'head.clientname', 'head.createby', 'head.editby', 'head.viewby', 'num.postedby', 'head.yourref', 'head.ourref'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        } else {
            $limit = 'limit 150';
        }


        $qry = "select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid, 'DRAFT' as status,
        head.createby,head.editby,head.viewby,num.postedby,num.postdate,head.createdate,
        head.yourref, head.ourref  
        from " . $this->head . " as head left join " . $this->tablenum . " as num 
        on num.trno=head.trno where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " $filtersearch
        union all
        select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid,'POSTED' as status,
        head.createby,head.editby,head.viewby, num.postedby,num.postdate,head.createdate,
        head.yourref, head.ourref 
        from " . $this->hhead . " as head left join " . $this->tablenum . " as num 
        on num.trno=head.trno where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " $filtersearch
        $orderby " . $limit;

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
            'lock',
            'unlock',
            'post',
            'unpost',
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
            $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'so', 'title' => 'SO_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
        }

        switch ($config['params']['companyid']) {
            case 19: //housegem
                $buttons['others']['items']['downloadexcel'] = ['label' => 'Download RO (Excel)', 'todo' => ['type' => 'downloadexcel', 'action' => 'downloadexcel', 'lookupclass' => 'downloadexcel', 'access' => 'view']];
                break;
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

        $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

        return $return;
    }

    public function createHeadField($config)
    {
        $companyid = $config['params']['companyid'];
        $systemtype = $this->companysetup->getsystemtype($config['params']);

        // col 1
        $fields = ['docno', 'client', 'clientname', 'helpername', 'helpername2'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'docno.label', 'Transaction#');
        data_set($col1, 'client.lookupclass', 'employeedriver');
        data_set($col1, 'client.label', 'Driver Code');
        data_set($col1, 'clientname.label', 'Driver Name');
        data_set($col1, 'helpername.label', 'Helper 1');
        data_set($col1, 'helpername.action', 'lookupclient');
        data_set($col1, 'helpername2.label', 'Helper 2');
        data_set($col1, 'helpername2.action', 'lookupclient');

        // col 2
        $fields = ['dateid', 'truck', 'area', 'contact', 'checked'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'contact.label', 'Contact No.');
        data_set($col2, 'truck.required', false);
        data_set($col2, 'truck.type', 'input');
        data_set($col2, 'truck.readonly', false);
        data_set($col2, 'area.type', 'input');
        data_set($col2, 'area.readonly', false);

        // col 3
        $fields = ['odoin', 'odoout', 'amt'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'rem.required', false);
        data_set($col3, 'amt.label', 'Petty Cash');


        $fields = ['rem'];
        $col4 = $this->fieldClass->create($fields);

        return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
    }

    public function createnewtransaction($docno, $params)
    {
        $data = [];
        $data[0]['trno'] = 0;
        $data[0]['docno'] = $docno;
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['client'] = '';
        $data[0]['clientname'] = '';
        $data[0]['helperid'] = 0;
        $data[0]['helpername'] = '';
        $data[0]['helpername2'] = '';
        $data[0]['truckid'] = 0;
        $data[0]['plateno'] = '';
        $data[0]['area'] = '';
        $data[0]['contact'] = '';
        $data[0]['checked'] = '';
        $data[0]['odoin'] = '0.00';
        $data[0]['odoout'] = '0.00';
        $data[0]['amt'] = 0;
        $data[0]['rem'] = '';

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


        $head = [];
        $islocked = $this->othersClass->islocked($config);
        $isposted = $this->othersClass->isposted($config);
        $table = $this->head;
        $htable = $this->hhead;
        $addfield = "";

        $qryselect = "select 
         num.center,
         head.trno, 
         head.docno,
         client.client,
         left(head.dateid,10) as dateid, 
         head.clientname,
         info.helperid,
         ifnull(hp.clientname, '') as helpername,
         info.helperid2,
         ifnull(hpp.clientname, '') as helpername2,  
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,info.plateno,
         head.address as area,head.tel as contact,head.checkedby as checked,
         head.odoin,head.odoout,head.amt";

        $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client   
        left join headinfotrans as info on info.trno=head.trno     
        left join client as hp on hp.clientid=info.helperid
        left join client as hpp on hpp.clientid=info.helperid2
        where head.trno = ? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join hheadinfotrans as info on info.trno=head.trno
        left join client as hp on hp.clientid=info.helperid
        left join client as hpp on hpp.clientid=info.helperid2
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

            $gpqry = "select sum(ext) as value from (select stock.weight * stock.iss as ext from $this->stock as stock where stock.trno =? 
        union all select stock.weight * stock.iss as ext from $this->hstock as stock where stock.trno = ?) as a ";
            $gpext = round($this->coreFunctions->datareader($gpqry, [$head[0]->trno, $head[0]->trno]), 2);
            $head[0]->ext = number_format($gpext, $this->companysetup->getdecimal('price', $config['params']));

            $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
            $hideobj = [];
            // $hideobj['lbltaxes'] = true;
            // $hideobj['forwtinput'] = false;

            // $loaddate = $head[0]->loaddate;
            // if ($loaddate != null) {
            //   $hideobj['lbltaxes'] = false;
            //   $hideobj['forwtinput'] = true;
            // }


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
        $dataOthers = [];
        if ($isupdate) {
            unset($this->fields[1]);
            unset($head['docno']);
        }

        $dateTables = ['rohead'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

        foreach ($this->fields as $key) {
            if (array_key_exists($key, $head)) {
                $data[$key] = $head[$key];
                if (!in_array($key, $this->except)) {
                    $data[$key] = $this->othersClass->sanitizekeyfieldFast($key, $data[$key], $lookups);
                } //end if    
            }
        }

        foreach ($this->fieldOthers as $key) {
            if (array_key_exists($key, $head)) {
                $dataOthers[$key] = $head[$key];
                if (!in_array($key, $this->except)) {
                    $dataOthers[$key] = $this->othersClass->sanitizekeyfieldFast($key, $dataOthers[$key], $lookups);
                } //end if    
            }
        }

        $data['address'] = $head['area'];
        $data['tel'] = $head['contact'];
        $data['checkedby'] = $head['checked'];

        // var_dump($data);

        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        if ($isupdate) {
            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);

            $dataOthers['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $dataOthers['editby'] = $config['params']['user'];
            $this->coreFunctions->sbcupdate('headinfotrans', $dataOthers, ['trno' => $head['trno']]);
        } else {
            $data['doc'] = $config['params']['doc'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];

            $newtrno = $this->coreFunctions->sbcinsert($this->head, $data);
            if ($newtrno) {
                $dataOthers['trno'] = $newtrno; // ensure headinfotrans links to the right trno
                $this->coreFunctions->sbcinsert('headinfotrans', $dataOthers);
            }

            $this->logger->sbcwritelog($newtrno, $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
        }
    } // end function

    public function createTab($access, $config)
    {
        $fields = ['creditinfo'];
        $col1 = $this->fieldClass->create($fields);
        $iscreateversion = $this->companysetup->getiscreateversion($config['params']);
        $so_btnvoid_access = $this->othersClass->checkAccess($config['params']['user'], 3593);
        $iskgs = $this->companysetup->getiskgs($config['params']);



        $column = ['action', 'docno', 'clientname', 'amt', 'terms', 'agent', 'modeofpayment', 'rem'];
        foreach ($column as $key => $value) {
            $$value = $key;
        }
        $headgridbtns = ['viewref'];


        $tab = [
            $this->gridname => [
                'gridcolumns' => $column,
                'headgridbtns' => $headgridbtns
            ],
        ];

        $stockbuttons = ['save', 'delete'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        //styling 
        $obj[0]['inventory']['columns'][$docno]['label'] = 'DR#';
        $obj[0]['inventory']['columns'][$clientname]['label'] = 'CUSTOMER';
        $obj[0]['inventory']['columns'][$modeofpayment]['label'] = 'PAYMENT';
        $obj[0]['inventory']['columns'][$rem]['label'] = 'REMARKS';
        $obj[0]['inventory']['columns'][$docno]['readonly'] = true;
        $obj[0]['inventory']['columns'][$clientname]['readonly'] = true;
        $obj[0]['inventory']['columns'][$amt]['readonly'] = true;
        $obj[0]['inventory']['columns'][$terms]['readonly'] = true;
        $obj[0]['inventory']['columns'][$modeofpayment]['readonly'] = true;
        $obj[0]['inventory']['columns'][$rem]['readonly'] = true;

        //style
        $obj[0]['inventory']['columns'][$docno]['style'] = 'width: 50px;whiteSpace: normal;min-width:50px;max-width:50px';
        $obj[0]['inventory']['columns'][$clientname]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px';
        $obj[0]['inventory']['columns'][$modeofpayment]['style'] = 'width: 50px;whiteSpace: normal;min-width:50px;max-width:50px';
        $obj[0]['inventory']['columns'][$amt]['style'] = 'width: 50px;whiteSpace: normal;min-width:50px;max-width:50px';
        $obj[0]['inventory']['columns'][$terms]['style'] = 'width: 50px;whiteSpace: normal;min-width:50px;max-width:50px';
        $obj[0]['inventory']['columns'][$agent]['style'] = 'width: 50px;whiteSpace: normal;min-width:50px;max-width:50px';
        $obj[0]['inventory']['columns'][$modeofpayment]['style'] = 'width: 70px;whiteSpace: normal;min-width:70px;max-width:70px';
        $obj[0]['inventory']['columns'][$rem]['style'] = 'width: 70px;whiteSpace: normal;min-width:70px;max-width:70px; text-align:right;';

        // remove the header per row
        $obj[0]['inventory']['descriptionrow'] = [];

        $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['pendingsjsummary', 'saveitem', 'deleteallitem'];

        foreach ($tbuttons as $key => $value) {
            $$value = $key;
        }

        $obj = $this->tabClass->createtabbutton($tbuttons);

        // Configure the button for employee lookup
        $obj[$pendingsjsummary]['label'] = "ADD SJ";
        $obj[$saveitem]['label'] = "SAVE ALL";
        $obj[$deleteallitem]['label'] = "DELETE ALL";
        $obj[$pendingsjsummary]['lookupclass'] = "pendingsjdetail";
        $obj[$addempgrid]['action'] = "getpendingsj";


        return $obj;
    }

    public function stockstatus($config)
    {
        switch ($config['params']['action']) {
            case 'pendingsjsummary':
                return $this->getpendingsj($config);
                // case 'saveitem':
                //     return $this->saveitem($config);
                // case 'saveperitem':
                //     return $this->saveperitem($config);
                //     break;
                // case 'deleteitem':
                //     return $this->deleteitem($config);
                // case 'deleteallitem':
                //     return $this->deleteallitem($config);
            default:
                return ['status' => false, 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        }
    }

    public function openstock($trno, $config)
    {
        $qry = "select detail.trno, detail.line, detail.sjtrno, detail.docno, detail.clientname,
        detail.amt, detail.terms, detail.agent, detail.rem, detail.consignpr, detail.disc2,
        detail.limitcheck, '' as modeofpayment,
        case when detail.limitcheck = 2 then 'bg-red-2'
             when detail.limitcheck = 1 then 'bg-yellow-2'
             else '' end as bgcolor
        from " . $this->stock . " as detail
        left join client as ag on ag.client = detail.agent
        where detail.trno = ?
        order by detail.line";
        return $this->coreFunctions->opentable($qry, [$trno]);
    }

    public function getpendingsj($config)
    {
        $center = $config['params']['center'];
        $trno = $config['params']['trno'];

        $qry = "select head.trno, head.docno, head.clientname, head.terms, head.agent, 
        ifnull(sum(stock.ext),0) as amt
        from lahead as head
        left join lastock as stock on stock.trno = head.trno
        left join cntnum on cntnum.trno = head.trno
        where head.doc = 'SJ' and cntnum.center = ? and cntnum.postdate is null
        and not exists (select 1 from " . $this->head . " as d where d.sjtrno = head.trno and d.trno = ?)
        group by head.trno, head.docno, head.clientname, head.terms, head.agent, head.rem
        order by head.docno desc";

        $data = $this->coreFunctions->opentable($qry, [$center, $trno]);
        return ['status' => true, 'data' => $data, 'msg' => 'Loaded pending SJ.'];
    }

    // public function saveitem($config)
    // {
    //     $trno = $config['params']['trno'];
    //     $rows = isset($config['params']['row']) ? $config['params']['row'] : [];


    //     if (empty($rows)) {
    //         return ['status' => false, 'msg' => 'No items to save.'];
    //     }

    //     $saved = 0;

    //     foreach ($rows as $key => $value) {
    //         // only push rows the grid actually marked as edited
    //         if (!isset($value['bgcolor']) || $value['bgcolor'] == '') {
    //             continue;
    //         }

    //         $line = $value['line'];
    //         $rate = $value['rate'];

    //         $data = [
    //             'rate' => $rate,
    //             'editdate' => $this->othersClass->getCurrentTimeStamp(),
    //             'editby' => $config['params']['user']
    //         ];

    //         $this->coreFunctions->sbcupdate($this->detail, $data, ['trno' => $trno, 'line' => $line]);
    //         $this->logger->sbcwritelog($trno, $config, 'STOCK', 'UPDATE - Line:' . $line . ' Rate:' . $rate);
    //         $saved++;
    //     }

    //     if ($saved == 0) {
    //         return ['status' => false, 'msg' => 'No edited items to save.'];
    //     }

    //     $row = $this->openstock($trno, $config);

    //     return ['inventory' => $row, 'status' => true, 'msg' =>  ' Successfully saved.', 'reloadhead' => true, 'trno' => $config['params']['trno']];
    // }

    // public function saveperitem($config)
    // {
    //     $trno = $config['params']['trno'];
    //     $row = $config['params']['row'];
    //     $line = $row['line'];
    //     $rate = $row['rate'];

    //     $data = [
    //         'rate' => $rate,
    //         'editdate' => $this->othersClass->getCurrentTimeStamp(),
    //         'editby' => $config['params']['user']
    //     ];

    //     $update = $this->coreFunctions->sbcupdate($this->detail, $data, ['trno' => $trno, 'line' => $line]);

    //     if ($update) {
    //         $this->logger->sbcwritelog($trno, $config, 'STOCK', 'UPDATE - Line:' . $line . ' empid:' . $row['empid'] . ' Rate:' . $rate);
    //         $returnrow = $this->openstockline($config, [$line]);
    //         return ['row' => $returnrow, 'status' => true, 'msg' => 'Successfully saved.'];
    //     } else {
    //         return ['status' => false, 'msg' => 'Update failed.'];
    //     }
    // }

    // public function deleteitem($config)
    // {
    //     $trno = $config['params']['row']['trno'];
    //     $line = $config['params']['row']['line'];
    //     $this->coreFunctions->execqry("delete from " . $this->detail . " where trno=? and line=?", 'delete', [$trno, $line]);
    //     $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line);
    //     return ['status' => true, 'msg' => 'Item was successfully deleted.'];
    // }

    // public function deleteallitem($config)
    // {
    //     $trno = $config['params']['trno'];
    //     $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);
    //     return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
    // }


    public function deletetrans($config)
    {
        $trno = $config['params']['trno'];
        $doc = $config['params']['doc'];
        $table = $config['docmodule']->tablenum;
        $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
        $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
        $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);

        $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from headinfotrans where trno=?', 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
        $this->othersClass->deleteattachments($config);
        $this->logger->sbcdel_log($trno, $config, $docno);
        return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
    } //end function


    public function posttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];
        $companyid = $config['params']['companyid'];

        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        if ($this->othersClass->isposted($config)) {
            return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
        }

        $qry = "insert into " . $this->hhead . "(trno,doc,docno,client,clientname,dateid,rem,address,tel,checkedby,odoin,odoout,amt,createdate,createby,editby,editdate,lockdate,lockuser)
        SELECT head.trno,head.doc, head.docno,head.client, head.clientname,head.dateid as dateid, head.rem,head.address,head.tel,head.checkedby,head.odoin,head.odoout,head.amt,head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser
        FROM " . $this->head . " as head left join cntnum on cntnum.trno=head.trno
        where head.trno=? limit 1";
        $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

        if ($posthead) {
            if (!$this->othersClass->postingheadinfotrans($config)) {
                $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
                return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while posting head data.'];
            }

            $date = $this->othersClass->getCurrentTimeStamp();
            $data = ['postdate' => $date, 'postedby' => $config['params']['user'], 'statid' => 5];
            $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
            $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry('delete from headinfotrans where trno=?', 'delete', [$trno]);
            $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
            $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
            return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
        } else {
            return ['status' => false, 'msg' => 'Error on Posting Head'];
        }
    } //end function


    public function unposttrans($config)
    {
        $companyid = $config['params']['companyid'];
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];

        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        $qry = "insert into " . $this->head . "(trno,doc,docno,client,clientname,dateid,rem,address,tel,checkedby,odoin,odoout,amt,createdate,createby,editby,editdate,lockdate,lockuser)
        select head.trno,head.doc, head.docno,head.client, head.clientname,head.dateid as dateid, head.rem,head.address,head.tel,head.checkedby,head.odoin,head.odoout,head.amt,head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser
        from (" . $this->hhead . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno)left join client on client.client=head.client
        where head.trno=? limit 1";

        if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {

            if (!$this->othersClass->unpostingheadinfotrans($config)) {
                $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
                return ['trno' => $trno, 'status' => false, 'msg' => 'Unposting failed. There are issues with the head data.'];
            }

            $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
            $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry('delete from hheadinfotrans where trno=?', 'delete', [$trno]);
            $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
            return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
        } else {
            return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED.'];
        }
    } //end function


} //end class
