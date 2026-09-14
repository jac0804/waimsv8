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
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;

use Exception;

class hb
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Beginning and Closing Balance';
  public $gridname = 'accounting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $tablenum = 'hrisnum';
  public $head = 'hbhead';
  public $hhead = 'hhbhead';
  public $detail = 'hbdetail';
  public $hdetail = 'hhbdetail';
  public $tablelogs = 'hrisnum_log';
  public $tablelogs_del = 'del_hrisnum_log';
  private $stockselect;
  public $defaultContra = 'IS1';

  private $fields = ['trno', 'docno', 'dateid', 'empid', 'line', 'pacnoid','uom','db','cr','rem'];

  private $except = ['trno', 'dateid'];
  public $acctg = [];
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
        $this->sqlquery = new sqlquery;
        $this->reporter = new SBCPDF;
        $this->helpClass = new helpClass;
    }
    public function getAttrib()
    {
        $attrib = array(
        'view' => 5978,
        'edit' => 5979,
        'new' => 5980,
        'save' => 5981,
        'delete' => 5982,
        'print' => 5983,
        'lock' => 5984,
        'unlock' => 5985,
        'post' => 5986,
        'unpost' => 5987,
        'additem' => 5988,
        'edititem' => 5989,
        'deleteitem' => 5990
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $companyid = $config['params']['companyid'];
        // $action = 0;
        // $liststatus = 1;
        // $listdocument = 2;
        // $listdate = 3;
        // $listclientname = 4;
        // $yourref = 5;
        // $ourref = 6;
        // $postdate = 7;
        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
        $stockbuttons = ['view', 'duplicatedoc'];
        foreach ($getcols as $key => $value) {
        $$value = $key;
        }
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

        $cols[$action]['style'] = 'width:140px;whiteSpace: normal;min-width:140px;';
        $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $cols[$postdate]['label'] = 'Post Date';
    
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
        $limit = '';
        $filtersearch = "";


        switch ($itemfilter) {
        case 'draft':
            $condition = ' and num.postdate is null ';
            break;
        case 'posted':
            $condition = ' and num.postdate is not null ';
            break;
        }

        if (isset($config['params']['search'])) {
        $searchfield = ['head.docno', 'head.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
        $search = $config['params']['search'];
        if ($search != "") {
            $filtersearch = $this->othersClass->multisearch($searchfield, $search);
        }
        }

        $join = "";
        $hjoin = "";
        $field = "";
        
        $dateid = "left(head.dateid,10) as dateid";
        if ($searchfilter == "") $limit = 'limit 150';
        $orderby =  "order by  dateid desc, docno desc";
    
        $qry = "select head.trno,head.docno,head.empid,$dateid, 'DRAFT' as status,
        head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate    $field       
        from " . $this->head . " as head 
        left join " . $this->tablenum . " as num   on num.trno=head.trno   " . $join . "
        where num.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . "  " . $filtersearch . "
        union all
        select head.trno,head.docno,head.empid,$dateid,'POSTED' as status,
        head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate    $field      
        from " . $this->hhead . " as head 
        left join " . $this->tablenum . " as num  on num.trno=head.trno    " . $hjoin . "
        where num.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . "  " . $filtersearch . "
        $orderby $limit";


        $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }

    public function createHeadbutton($config)
    {
        $companyid = $config['params']['companyid'];
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
        $buttons['others']['items'] = [
        'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
        'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
        'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
        'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
        ];
        return $buttons;
    } 

    public function createTab($access, $config)
    {
        $action = 0;
        $acnoname = 1;
        $uom = 2;
        $db = 3;
        $cr = 4;
        $rem = 5;

        $columns = [
            'action',
            'acnoname',
            'uom',
            'db',
            'cr',
            'rem'
        ];

        $tab = [
            $this->gridname => [
            'gridcolumns' => $columns,
            'headgridbtns' => []
            ]
        ];

        $stockbuttons = ['save', 'delete'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$acnoname]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$uom]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$db]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px;';
        $obj[0][$this->gridname]['columns'][$cr]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px;';
        $obj[0][$this->gridname]['columns'][$rem]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px;';
        $obj[0]["accounting"]["label"] = "PAYROLL ACCOUNT";
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [ 'additem','saveitem', 'deleteallitem'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[0]['label'] = "ADD ACCOUNT";
        $obj[0]['lookupclass'] = 'lookupoacnodetail';
        $obj[0]['action'] = 'addpacno';
        $obj[1]['label'] = "SAVE ACCOUNT";
        $obj[2]['label'] = "DELETE ACCOUNT";    
        return $obj;
    }

    public function createHeadField($config)
    {
        $fields = ['docno','empcode'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'empcode.name', 'empname');
        data_set($col1, 'empcode.label', 'Employee Name');
        $fields = ['dateid'];
        $col2 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2);
    }

    public function createnewtransaction($docno, $params)
    {
        $data = [];
        $data[0]['trno'] = 0;
        $data[0]['docno'] = $docno;
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['empid'] = 0;
        $data[0]['empname'] = '';
        return $data;
    }

    public function loadheaddata($config)
    {
        $doc = $config['params']['doc'];
        $trno = $config['params']['trno'];
        $center = $config['params']['center'];
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
        $center = $config['params']['center'];

        $head = [];
        $islocked = $this->othersClass->islocked($config);
        $isposted = $this->othersClass->isposted($config);
        $table = $this->head;
        $htable = $this->hhead;
        $qryselect = "select 
            num.center,
            head.trno, 
            head.docno,
            head.empid,
            emp.clientname as empname,
            left(head.dateid,10) as dateid, 
            date_format(head.createdate,'%Y-%m-%d') as createdate ";

        $qry = $qryselect . " from $table as head
            left join $tablenum as num on num.trno = head.trno
            left join client as emp on head.empid = emp.clientid
            where head.trno = ? and num.doc=? and num.center = ? 
            union all " . $qryselect . " from $htable as head
            left join $tablenum as num on num.trno = head.trno
            left join client as emp on head.empid = emp.clientid
            where head.trno = ? and num.doc=? and num.center=? ";

        $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
        if (!empty($head)) {
            $detail = $this->opendetail($trno, $config);
            $viewdate = $this->othersClass->getCurrentTimeStamp();
            $viewby = $config['params']['user'];
            $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
            $msg = $config['msg'];
            }
            return  ['head' => $head, 'griddata' => ['accounting' => $detail], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg];
        } else {
            $head[0]['trno'] = 0;
            $head[0]['docno'] = '';
            return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['accounting' => []], 'msg' => 'Data Head Fetched Failed, either somebody already deleted the transaction or modified...'];
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

        $dateTables = [$this->head];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'],  $companyid, [], false, $dateTables);

        foreach ($this->fields as $key) {
        if (array_key_exists($key, $head)) {
            $data[$key] = $head[$key];
            if (!in_array($key, $this->except)) {
            $data[$key] = $this->othersClass->sanitizekeyfieldFast($key, $data[$key], $lookups);
            } //end if    
        }
        }
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        if ($isupdate) {
        $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
        } else {
        $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['createby'] = $config['params']['user'];
        $this->coreFunctions->sbcinsert($this->head, $data);
        $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['empid']);
        }
    } // end function

    public function deletetrans($config)
    {
        $trno = $config['params']['trno'];
        $doc = $config['params']['doc'];
        $docno = $this->coreFunctions->datareader("select docno as value from " . $this->tablenum . ' where trno=?', [$trno]);
        $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
        $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);
        $this->deleteallitem($config);
        $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
        $this->logger->sbcdel_log($trno, $config, $docno);
        return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
    }

    public function posttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];
        $doc = $config['params']['doc'];

        $docno = $this->coreFunctions->datareader('select docno as value from ' . $config['docmodule']->tablenum . ' where trno=?', [$trno]);
        $msg = '';

        //less than 0 check
        $isnegative = $this->coreFunctions->opentable("select trno from " . $this->detail . " where trno=? and (db < 0 or cr < 0) limit 1", [$trno]);
        if (!empty($isnegative)) {
            return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Negative debit or credit are not allowed. Please check.'];
        }
        //balance check
        $bal = $this->coreFunctions->getfieldvalue($this->detail, 'sum(db-cr)', 'trno=?', [$trno]);
        if ($bal == '' || $bal == null) {
            $bal = 0;
        }
        if ($bal != 0) {
            return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Accounting entries are not balance.'];
        }

        $qry = "insert into " . $this->hhead . " (trno, docno, dateid, empid, createby, createdate, editby, editdate, viewby, viewdate, lockdate)
                select trno, docno, dateid, empid, createby, createdate, editby, editdate, viewby, viewdate, lockdate
                from " . $this->head . " where trno=?";
        $this->coreFunctions->execqry($qry, 'insert', [$trno]);

        $qry = "insert into " . $this->hdetail . " (trno, line, pacnoid, uom, db, cr, rem, createby, createdate, editby, editdate)
                select trno, line, pacnoid, uom, db, cr, rem, createby, createdate, editby, editdate
                from " . $this->detail . " where trno=?";
        $this->coreFunctions->execqry($qry, 'insert', [$trno]);

        $date = $this->othersClass->getCurrentTimeStamp();
        $data = ['postdate' => $date, 'postedby' => $user];
        $this->coreFunctions->sbcupdate($config['docmodule']->tablenum, $data, ['trno' => $trno]);
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->detail . " where trno=?", "delete", [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
    } 

    public function unposttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];
        $doc = $config['params']['doc'];

        $docno = $this->coreFunctions->datareader('select docno as value from ' . $config['docmodule']->tablenum . ' where trno=?', [$trno]);
        $msg = '';

        $qry = "insert into " . $this->head . " (trno, docno, dateid, empid, createby, createdate, editby, editdate, viewby, viewdate, lockdate)
                select trno, docno, dateid, empid, createby, createdate, editby, editdate, viewby, viewdate, lockdate
                from " . $this->hhead . " where trno=?";
        $this->coreFunctions->execqry($qry, 'insert', [$trno]);

        $qry = "insert into " . $this->detail . " (trno, line, pacnoid, uom, db, cr, rem, createby, createdate, editby, editdate)
                select trno, line, pacnoid, uom, db, cr, rem, createby, createdate, editby, editdate
                from " . $this->hdetail . " where trno=?";
        $this->coreFunctions->execqry($qry, 'insert', [$trno]);

        $this->coreFunctions->sbcupdate($config['docmodule']->tablenum, ['postdate' => null, 'postedby' => ''], ['trno' => $trno]);
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hdetail . " where trno=?", "delete", [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
    } 

    private function getdetailselect($config)
    {
        $qry = "head.trno,left(head.dateid,10) as dateid,d.line,pa.code as acno,pa.codename as acnoname,
                d.uom,d.db,d.cr,d.rem,0 as refx,'' as bgcolor,'' as errcolor";
        return $qry;
    }

    public function opendetail($trno, $config)
    {
        $sqlselect = $this->getdetailselect($config);
        $qry = "select " . $sqlselect . " 
        from " . $this->detail . " as d
        left join " . $this->head . " as head on head.trno=d.trno
        left join paccount as pa on d.pacnoid=pa.line
        where d.trno=?
        union all
        select " . $sqlselect . "  
        from " . $this->hdetail . " as d
        left join " . $this->hhead . " as head on head.trno=d.trno
        left join paccount as pa on d.pacnoid=pa.line
        where d.trno=? order by line
        ";
        $detail = $this->coreFunctions->opentable($qry, [$trno, $trno]);
        return $detail;
    }

    public function opendetailline($config)
    {
        $sqlselect = $this->getdetailselect($config);
        $trno = $config['params']['trno'];
        $line = $config['params']['line'];
        $qry = "select " . $sqlselect . " 
        from " . $this->detail . " as d
        left join " . $this->head . " as head on head.trno=d.trno
        left join paccount as pa on d.pacnoid=pa.line
        where d.trno=? and d.line=?";
        $detail = $this->coreFunctions->opentable($qry, [$trno, $line]);
        return $detail;
    } 

    public function stockstatus($config)
    {
        switch ($config['params']['action']) {
            // case 'additem':
            // return $this->additem('insert', $config);
            // break;
            case 'addpacno':
            return $this->addpacno($config);
            break;
            case 'deleteallitem':
            return $this->deleteallitem($config);
            break;
            case 'deleteitem':
            return $this->deleteitem($config);
            break;
            case 'saveitem': //save all detail edited
            return $this->updateitem($config);
            break;
            case 'saveperitem':
            return $this->updateperitem($config);
            break;
            default:
            return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
            break;
        }
    }

    public function additem($action, $config)
    {
        $companyid = $config['params']['companyid'];
        if (isset($config['params']['trno'])) {
            $trno = $config['params']['trno'];
        } else {
            $trno = isset($config['params']['data']['trno']) ? $config['params']['data']['trno'] : $this->othersClass->readprofile('TRNO', $config);
        }

        $acno = $config['params']['data']['acno'];
        $pacnoid = $this->coreFunctions->getfieldvalue('paccount', 'line', 'code=?', [$acno]);
        $uom = $config['params']['data']['uom'];
        $db = $config['params']['data']['db'];
        $cr = $config['params']['data']['cr'];
        $rem = $config['params']['data']['rem'];

        $line = 0;
        if ($action == 'insert') {
            $qry = "select line as value from " . $this->detail . " where trno=? order by line desc limit 1";
            $line = $this->coreFunctions->datareader($qry, [$trno]);
            if ($line == '') {
            $line = 0;
            }
            $line = $line + 1;
            $config['params']['line'] = $line;
        } elseif ($action == 'update') {
            $line = $config['params']['data']['line'];
            $config['params']['line'] = $line;
        }

        $data = [
            'trno' => $trno,
            'line' => $line,
            'pacnoid' => $pacnoid,
            'uom' => $uom,
            'db' => $db,
            'cr' => $cr,
            'rem' => $rem
        ];

        $dateTables = ['hbdetail'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

        foreach ($data as $key => $value) {
            $data[$key] = $this->othersClass->sanitizekeyfieldFast($key, $data[$key], $lookups);
        }

        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $data['editdate'] = $current_timestamp;
        $data['editby'] = $config['params']['user'];
        $msg = '';
        $status = true;

        if ($action == 'insert') {
            $data['createby'] = $config['params']['user'];
            $data['createdate'] = $current_timestamp;
        } elseif ($action == 'update') {
            if ($this->coreFunctions->sbcupdate($this->detail, $data, ['trno' => $trno, 'line' => $line]) == 1) {
            $status = true;
            } else {
            $status = false;
            }
            return ['status' => $status, 'msg' => ''];
        }
    } // end function

    public function addpacno($config)
    {
        $trno = $config['params']['trno'];
        $rows = $config['params']['rows'];
        $returnrows = [];

        foreach ($rows as $pacctrow) {

            $qry = "select line as value from " . $this->detail . " where trno=? order by line desc limit 1";
            $line = $this->coreFunctions->datareader($qry, [$trno]);
            if ($line == '')
                $line = 0;
            $line = $line + 1;

            $data = [
                'line' => $line,
                'trno' => $trno,
                'pacnoid' => $pacctrow['line'],
                'uom' => $pacctrow['uom'],
                'db' => 0.00,
                'cr' => 0.00,
                'rem' => ''
            ];
            $this->coreFunctions->sbcinsert($this->detail, $data);

            $config['params']['line'] = $line;
            $row = $this->opendetailline($config);
            if (!empty($row)) {
                array_push($returnrows, $row[0]);
            }
            $codename = $this->coreFunctions->getfieldvalue('paccount', 'codename', 'line=?', [$pacctrow['line']]);
            $this->logger->sbcwritelog($trno, $config, 'PAYROLLDETAIL', 'ADD - Line:' . $line . ' Code:' .$codename .'('. $pacctrow['acno'].')');
        }

        return ['status' => true, 'msg' => 'Account(s) added successfully...', 'row' => $returnrows, 'reloaddata' => true];
    } // end function
    

    public function deleteallitem($config)
    {
        $trno = $config['params']['trno'];
        $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'PAYROLLDETAIL', 'DELETED ALL PACCOUNT ENTRIES');
        return ['status' => true, 'msg' => 'Successfully deleted.', 'accounting' => []];
    }

    public function deleteitem($config)
    {
        $config['params']['trno'] = $config['params']['row']['trno'];
        $config['params']['line'] = $config['params']['row']['line'];
        $data = $this->opendetailline($config);
        $trno = $config['params']['trno'];
        $line = $config['params']['line'];
        $qry = "delete from " . $this->detail . " where trno=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);

        $data = json_decode(json_encode($data), true);
        $this->logger->sbcwritelog($trno, $config, 'PAYROLLDETAIL', 'REMOVED - Line:' . $line . ' Code:' . $data[0]['acno'] . ' DB:' . $data[0]['db'] . ' CR:' . $data[0]['cr']);
        return ['status' => true, 'msg' => 'Account was successfully deleted.'];
    } // end function

    public function updateitem($config)
    {
        foreach ($config['params']['row'] as $key => $value) {
            $config['params']['data'] = $value;
            $isupdate = $this->additem('update', $config);
            if ($isupdate['status'] == false) {
            break;
            }
        }
        $data = $this->opendetail($config['params']['trno'], $config);
        $data2 = json_decode(json_encode($data), true);
        $msg1 = '';
        foreach ($data2 as $key => $value) {
             $dbValue = floatval(isset($value['db']) ? $value['db'] : 0);
            $crValue = floatval(isset($value['cr']) ? $value['cr'] : 0);
            if ($dbValue == 0 && $crValue == 0) {
            $data[$key]->errcolor = 'bg-red-2';
            $isupdate['status'] = false;
            $msg1 = ' Some entries have zero value both debit and credit ';
            }
        }
        if ($isupdate['status']) {
            return ['accounting' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
        } else {
            return ['accounting' => $data, 'status' => true, 'msg' => 'Please check, some items have zero qty (' . $msg1 . ')'];
        }
    } //end function

    public function updateperitem($config)
    {
        $config['params']['data'] = $config['params']['row'];
        $isupdate = $this->additem('update', $config);
        $data = $this->opendetailline($config);
        if (!$isupdate['status']) {
            $data[0]->errcolor = 'bg-red-2';
            return ['row' => $data, 'status' => true, 'msg' => 'Update failed.'];
        } else {
            return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
        }
    }

      public function stockstatusposted($config)
    {
        switch ($config['params']['action']) {
        case 'duplicatedoc':
            return $this->othersClass->duplicateTransaction($config);
            break;
        case 'navigation':
            return $this->othersClass->navigatedocno($config);
            break;
        default:
            return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
            break;
        }
    }

    public function reportsetup($config)
    {
        $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
        $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

        $modulename = $this->modulename;
        $data = [];
        $style = 'width:500px;max-width:500px;';
        return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false, 'reloadhead' => true];
    }

    public function reportdata($config)
    {
        $this->logger->sbcviewreportlog($config);

        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);


        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'reloadhead' => true];
    }


    

}