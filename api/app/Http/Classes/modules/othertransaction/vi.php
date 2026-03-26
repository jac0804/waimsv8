<?php

namespace App\Http\Classes\modules\othertransaction;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;

class vi
{

    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'VIOLATION';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    public $expirystatus = ['readonly' => false, 'show' => true, 'showdate' => true];
    public $tablenum = 'hrisnum';
    public $head = 'violation';
    public $hhead = 'hviolation';
    public $detail = '';
    public $hdetail = '';
    public $tablelogs = 'hrisnum_log';
    public $tablelogs_del = 'del_hrisnum_log';
    private $stockselect;

    public $showfilterlabel = [
        ['val' => 'draft', 'label' => 'Entry', 'color' => 'primary'],
        ['val' => 'closed', 'label' => 'Closed', 'color' => 'primary'],
        ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary']
    ];
    private $fields = [
        'trno',
        'docno',
        'empid',
        'dateid',
        'remarks'
    ];
    private $except = ['trno'];
    private $acctg = [];
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;
    private $reporter;


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
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 5071,
            'edit' => 5072,
            'new' => 5073,
            'save' => 5074,
            'delete' => 5075,
            'print' => 5076,
            'lock' => 5077,
            'unlock' => 5078,
            'post' => 5079,
            'unpost' => 5080
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'empcode', 'empname'];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[1]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;';
        $cols[2]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[3]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[4]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[5]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';
        $cols[1]['align'] = 'text-left';

        return $cols;
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
        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['head.docno', 'c.clientname', 'c.client'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }

        switch ($itemfilter) {
            case 'draft':
                $condition = ' and num.postdate is null and head.closedate is null ';
                break;
            case 'closed':
                $condition = ' and head.closedate is not null and num.postdate is null ';
                break;
            case 'posted':
                $condition = ' and head.closedate is not null and num.postdate is not null ';
                break;
        }
        $qry = "select head.trno, head.docno, date(head.dateid) as dateid, c.client as empcode, c.clientname as empname, 
                case when head.closedate is not null then 'CLOSED' else 'DRAFT' end as status
                from " . $this->head . " as head left join client as c on c.clientid=head.empid left join " . $this->tablenum . " as num on num.trno=head.trno
                where head.doc=? and num.center = ? and date(head.dateid) between ? and ? " . $condition . " " . $filtersearch . "

                union all

                select head.trno, head.docno, date(head.dateid) as dateid, c.client as empcode, c.clientname as empname, 'POSTED' as status
                from " . $this->hhead . " as head left join client as c on c.clientid=head.empid left join " . $this->tablenum . " as num on num.trno=head.trno
                where head.doc=? and num.center = ? and date(head.dateid) between ? and ? " . $condition . " " . $filtersearch . "
                order by docno desc";

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
            'toggledown'
        );
        $buttons = $this->btnClass->create($btns);
        return $buttons;
    } // createHeadbutton 

    public function createTab($access, $config)
    {
        $tab = [];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        return $obj;
    }

    public function createtab2($access, $config)
    {
        $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entryhrisnumpicture', 'label' => 'Attachment', 'access' => 'view']];
        $obj = $this->tabClass->createtab($tab, []);
        $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
        return $return;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function createHeadField($config)
    {
        $companyid = $config['params']['companyid'];

        $fields = ['docno', 'dateid', 'empcode', 'empname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'empcode.action', 'lookupemployee');
        $fields = ['remarks'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'remarks.label', 'Reason');
        $fields = ['lblrem', 'forclosing'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'lblrem.label', 'CLOSED');
        data_set($col3, 'lblrem.style', 'font-weight:bold;font-size:15px;font-family:Century Gothic;color: green;');;


        $fields = [];
        $col4 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function createnewtransaction($docno, $params)
    {
        $data = [];
        $data[0]['trno'] = 0;
        $data[0]['docno'] = $docno;
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['empid'] = 0;
        $data[0]['empcode'] = '';
        $data[0]['empname'] = '';
        $data[0]['remarks'] = '';

        return $data;
    }

    public function loadheaddata($config)
    {
        $doc = $config['params']['doc'];
        $trno = $config['params']['trno'];
        $center = $config['params']['center'];

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

        $qryselect = "select head.trno, head.docno, head.empid, head.dateid,head.remarks,emp.client as empcode,
        emp.clientname as empname,head.closedate";
        $qry = $qryselect . " from " . $table . " as head
        left join client as emp on emp.clientid=head.empid
        left join $tablenum as num on num.trno = head.trno
        where num.trno = ? and num.doc='VI' and num.center=? 
        union all
        " . $qry = $qryselect . " from " . $htable . " as head
        left join client as emp on emp.clientid=head.empid
        left join $tablenum as num on num.trno = head.trno
        where num.trno = ? and num.doc='VI' and num.center=?";


        $head = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);
        if (!empty($head)) {
            $stock = [];
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }
            $hideobj = [];

            $closedate = $head[0]->closedate != null ? true : false;
            if ($closedate) {
                $hideobj['forclosing'] = true;
                $hideobj['lblrem'] = false;
            } else {
                $hideobj['forclosing'] = false;
                $hideobj['lblrem'] = true;
            }

            return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg, 'hideobj' => $hideobj];
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
            unset($this->fields['docno']);
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

        $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
        $this->logger->sbcdel_log($trno, $config, $docno);
        return ['trno' => 0, 'status' => true, 'msg' => 'Successfully deleted.'];
    } //end function

    public function posttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];
        $doc = $config['params']['doc'];
        $closedate = $this->coreFunctions->getfieldvalue($this->head, 'closedate', 'trno=?', [$trno]);
        if ($closedate == null) {
            return ['trno' => $trno, 'status' => false, 'msg' => "Can't post this transaction need to Close First!"];
        }

        $docno = $this->coreFunctions->datareader('select docno as value from ' . $config['docmodule']->tablenum . ' where trno=?', [$trno]);
        $msg = '';
        $qry = "insert into hviolation (trno,doc, docno, dateid, empid,remarks, createby,editby,editdate, lockdate, lockuser,posteddate, postedby,closeby,closedate)
                select trno,doc, docno, dateid, empid,remarks,createby,editby,editdate, lockdate, lockuser,posteddate, postedby,closeby,closedate
                from violation where trno=?";
        $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
        if ($result) {
            $date = $this->othersClass->getCurrentTimeStamp();
            $data = ['postdate' => $date, 'postedby' => $user];
            $this->coreFunctions->sbcupdate($config['docmodule']->tablenum, $data, ['trno' => $trno]);
            $this->coreFunctions->sbcupdate($config['docmodule']->hhead, $data, ['trno' => $trno]);
            $this->coreFunctions->execqry("delete from " . $config['docmodule']->head . " where trno=?", "delete", [$trno]);
            $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
            return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
        } else {
            $this->coreFunctions->execqry("delete from " . $config['docmodule']->hhead . " where trno=?", "delete", [$trno]);
            return ['trno' => $trno, 'status' => false, 'msg' => $msg];
        }
    } //end function

    public function unposttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];
        $doc = $config['params']['doc'];
        $msg = '';

        $docno = $this->coreFunctions->datareader('select docno as value from ' . $config['docmodule']->tablenum . ' where trno=?', [$trno]);

        $qry = "insert into violation (trno,doc, docno, dateid, empid,remarks, createby,editby,editdate, lockdate, lockuser,posteddate, postedby,closeby,closedate)
                select trno,doc, docno, dateid, empid,remarks,createby,editby,editdate, lockdate, lockuser,posteddate, postedby,closeby,closedate
                from hviolation where trno=?";
        $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

        if ($result) {
            $docno = $this->coreFunctions->getfieldvalue($config['docmodule']->tablenum, 'docno', 'trno=?', [$trno]);
            $this->coreFunctions->execqry("update " . $config['docmodule']->tablenum . " set postdate=null, postedby='' where trno=?", 'update', [$trno]);
            $this->coreFunctions->execqry("delete from " . $config['docmodule']->hhead . " where trno=?", "delete", [$trno]);
            $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
            return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
        } else {
            $this->coreFunctions->execqry("delete from " . $config['docmodule']->head . " where trno=?", "delete", [$trno]);
            return ['trno' => $trno, 'status' => false, 'msg' => $msg];
        }
    } //end function
    public function stockstatusposted($config)
    {
        $action = $config['params']['action'];
        switch ($action) {
            case 'forclosing':
                return $this->closedate($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
                break;
        }
    }
    public function closedate($config)
    {
        $trno = $config['params']['trno'];
        $date = $this->othersClass->getCurrentTimeStamp();
        $data = ['closedate' => $date, 'closeby' => $config['params']['user']];
        $update = $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $trno]);

        if ($update) {
            return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully Closed', 'backlisting' => true];
        } else {
            return ['trno' => $trno, 'status' => false, 'msg' => 'Failed to Closed', 'backlisting' => true];
        }
    }
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
}
