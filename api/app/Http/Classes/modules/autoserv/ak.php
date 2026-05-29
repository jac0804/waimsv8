<?php

namespace App\Http\Classes\modules\autoserv;

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



class ak
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'PACKAGE KITS';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
    public $tablenum = 'transnum';
    public $head = 'pthead';
    public $hhead = '';
    public $stock = 'ptjobs';
    public $hstock = '';
    public $tablelogs = 'transnum_log';
    public $statlogs = 'transnum_stat';
    public $tablelogs_del = 'del_transnum_log';
    public $htablelogs = 'htransnum_log';
    private $stockselect;
    private $sbcscript;

    private $fields = ['trno', 'docno', 'dateid', 'description', 'rem'];

    private $except = ['trno', 'dateid'];
    private $blnfields = [];
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;
    private $reporter;

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
        $this->sbcscript = new sbcscript;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 5845,
            'edit' => 5846,
            'new' => 5847,
            'save' => 5848,
            // 'change' => 67, remove change doc 5854
            'delete' => 5849,
            'print' => 5850,
            'additem' => 5851,
            'edititem' => 5852,
            'deleteitem' => 5853
        );

        return $attrib;
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
            'logs',
            'edit',
            'backlisting',
            'toggleup',
            'toggledown',
            'others'
        );

        $buttons = $this->btnClass->create($btns);

        $buttons['others']['items']['first'] =  ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']];
        $buttons['others']['items']['prev'] =  ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']];
        $buttons['others']['items']['next'] = ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']];
        $buttons['others']['items']['last'] = ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']];


        return $buttons;
    } // createHeadbutton

    public function createHeadField($config)
    {
        $fields = ['docno', 'rem'];

        $col1 = $this->fieldClass->create($fields);
        $fields = ['description'];

        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'description.label', '');

        $fields = [];
        $col3 = $this->fieldClass->create($fields);

        $fields = [];
        $col4 = $this->fieldClass->create($fields);

        return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
    }

    public function createTab($access, $config)
    {


        $column = ['action',  'code', 'description', 'rem'];
        foreach ($column as $key => $value) {
            $$value = $key;
        }
        $tab = [
            $this->gridname => [
                'gridcolumns' => $column
            ]

        ];
        $stockbuttons = ['save', 'delete', 'addtask'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0]['inventory']['columns'][$code]['type'] = 'label';
        $obj[0]['inventory']['columns'][$description]['type'] = 'label';
        $obj[0]['inventory']['columns'][$description]['label'] = 'Job Description';
        $obj[0][$this->gridname]['descriptionrow'] = [];
        $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }
    public function createtabbutton($config)
    {

        $tbuttons = ['jobsetup'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }
    public function createdoclisting($config)
    {

        $getcols = ['action', 'listdocument', 'listdate', 'listcreateby', 'listeditby', 'listviewby'];
        $stockbuttons = ['view'];
        foreach ($getcols as $key => $value) {
            $$value = $key;
        }
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

        // $cols[$action]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols = $this->tabClass->delcollisting($cols);
        return $cols;
    }
    public function loaddoclisting($config)
    {

        $date1 = date('Y-m-d', strtotime($config['params']['date1']));
        $date2 = date('Y-m-d', strtotime($config['params']['date2']));

        $doc = $config['params']['doc'];
        $center = $config['params']['center'];
        $status = "";
        $limit = '';

        $searchfield = [];
        $filtersearch = "";
        $search = $config['params']['search'];

        $status = "'draft'";
        if ($search != "") $limit = 'limit 150';
        $orderby = "order by dateid desc, docno desc";

        if (isset($config['params']['search'])) {
            $searchfield = ['head.docno', 'head.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];

            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }

        $qry = " 
        select head.trno, head.docno, $status as lblstatus, head.createby, head.editby, head.viewby
        from pthead as head 
        left join transnum as num on num.trno = head.trno
        where head.doc = ? and num.center = ? $filtersearch $orderby $limit";
        $data = $this->coreFunctions->opentable($qry, [$doc, $center]);
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
        $qry = "select head.trno, head.docno,head.doc,head.dateid,head.rem,head.description from pthead as head
        where head.trno = ?";
        $head = $this->coreFunctions->opentable($qry, [$trno]);

        if (!empty($head)) {

            $stock = $this->openstock($trno, $config);
            $viewdate = $this->othersClass->getCurrentTimeStamp();
            $viewby = $config['params']['user'];
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }
            $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
            $hidetabbtn = [];
            $clickobj = [];

            $hideobj = [];
            return  [
                'head' => $head,
                'griddata' => ['inventory' => $stock],
                'islocked' => false,
                'isposted' => false,
                'isnew' => false,
                'status' => true,
                'msg' => $msg,
                'clickobj' => [],
                'hidetabbtn' => [],
                'hideobj' => []
            ];
        } else {
            $head[0]['trno'] = 0;
            $head[0]['docno'] = '';
            return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
        }
    }
    public function getstockselect($config)
    {
        $query = "select pt.line,pt.jobid,pt.pttrno as trno,pt.rem,jt.docno as code,jt.jobtitle as description,'' as bgcolor ";
        return $query;
    }


    public function openstock($trno, $config)
    {
        $sqlselect = $this->getstockselect($config);
        $query = $sqlselect . " from ptjobs as pt
        left join jobthead as jt on jt.line = pt.jobid 
        where pt.pttrno = ?";
        $data = $this->coreFunctions->opentable($query, [$trno]);
        return $data;
    }
    public function openstockline($config)
    {
        $trno = $config['params']['trno'];
        $line = $config['params']['line'];
        $sqlselect = $this->getstockselect($config);
        $query = $sqlselect . " from ptjobs as pt 
        left join jobthead as jt on jt.line = pt.jobid 
        where pt.pttrno = ? and pt.line = ?";
        $data = $this->coreFunctions->opentable($query, [$trno, $line]);
        return $data;
    }

    public function createnewtransaction($docno, $params)
    {
        $data = [];
        $data[0]['trno'] = 0;
        $data[0]['docno'] = $docno;
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['description'] = '';
        $data[0]['rem'] = '';
        return $data;
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
        if ($isupdate) {
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
        } else {
            $data['doc'] = $config['params']['doc'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $insert = $this->coreFunctions->sbcinsert($this->head, $data);
            $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['description']);
        }
    }
    public function stockstatus($config)
    {
        switch ($config['params']['action']) {
            case 'additem':
                return $this->additem('insert', $config);
                break;
            // case 'addallitem': // save all item selected from lookup
            //     return $this->addallitem($config);
            //     break;
            case 'deleteitem':
                return $this->deleteitem($config);
                break;
            // case 'saveitem': //save all item edited
            //     return $this->updateitem($config);
            //     break;
            // case 'saveperitem':
            //     return $this->updateperitem($config);
            //     break;
            // case 'deleteallitem':
            //     return $this->deleteallitem($config);
            //     break;
            case 'getautojobsetup':
                return $this->getautojobsetup($config);
                break;
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
                break;
        }
    }
    public function getautojobsetup($config)
    {
        $trno = $config['params']['trno'];
        $rows = [];

        foreach ($config['params']['rows'] as $key => $value) {

            $query = "select line as jobid,docno as code,jobtitle as description from jobthead where line = ?";
            $data = $this->coreFunctions->opentable($query, [$value['line']]);

            foreach ($data as $key2 => $value2) {

                $config['params']['data']['jobid'] = $value2->jobid;
                $config['params']['data']['rem'] = '';
                $config['params']['trno'] = $trno;
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
        } //end foreach
        $this->coreFunctions->LogConsole(json_encode($rows));
        return ['row' => $rows, 'status' => true, 'msg' => 'Added Items Successful...'];
    }
    public function additem($action, $config)
    {

        $trno = $config['params']['trno'];

        if (isset($config['params']['data']['jobid'])) {
            $jobid = $config['params']['data']['jobid'];
        }
        if (isset($config['params']['data']['rem'])) {
            $rem = $config['params']['data']['rem'];
        }


        $data = [
            'pttrno' => $trno,
            'jobid' => $jobid,
            'rem' => $rem,
        ];

        foreach ($data as $key => $value) {
            $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        }
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        if ($action == 'insert') {
            $qry = "select line as value from " . $this->stock . " where pttrno=? order by line desc limit 1";
            $line = $this->coreFunctions->datareader($qry, [$trno]);
            if ($line == '') {
                $line = 0;
            }
            $line = $line + 1;
            $config['params']['line'] = $line;
            $data['line'] = $line;



            $data['encodeddate'] = $current_timestamp;
            $data['encodedby'] = $config['params']['user'];
            if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
                $query = "select line as jobid,docno as code,jobtitle as description from jobthead where line = ?";
                $job = $this->coreFunctions->opentable($query, [$jobid]);

                $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' Code:' . $job[0]->code . ' ' . ' Description: ' . $job[0]->description);
                $this->loadheaddata($config);
                $row = $this->openstockline($config);
                return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.', 'line' => $line, 'reloaddata' => true];
            } else {
                return ['status' => false, 'msg' => 'Add item Failed'];
            }
        } elseif ($action == 'update') {
            $config['params']['line'] = $config['params']['data']['line'];
            $line = $config['params']['data']['line'];

            $data['editdate'] = $current_timestamp;
            $data['editby'] = $config['params']['user'];
            $return = true;
            $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
            return $return;
        }
    }
    public function deleteitem($config)
    {
        $config['params']['trno'] = $config['params']['row']['trno'];
        $config['params']['line'] = $config['params']['row']['line'];
        $data = $this->openstockline($config);
        $trno = $config['params']['trno'];
        $line = $config['params']['line'];
        $this->coreFunctions->execqry('delete from ptjobs where trno=? and line=?', 'delete', [$trno, $line]);

        $data = json_decode(json_encode($data), true);
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' Job Description:' . $data[0]['description']);
        return ['status' => true, 'msg' => 'Item was successfully deleted.'];
    } // end function
}
