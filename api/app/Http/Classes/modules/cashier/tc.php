<?php

namespace App\Http\Classes\modules\cashier;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\URL;

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

class tc
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'PETTY CASH ENTRY';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $sqlquery;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
    public $tablenum = 'transnum';
    public $head = 'tchead';
    public $hhead = 'htchead';
    public $detail = 'tcdetail';
    public $hdetail = 'htcdetail';
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';
    public $htablelogs = 'htransnum_log';
    private $stockselect;
    // public $defaultContra = 'CR1';

    private $fields = [
        'trno',
        'docno',
        'dateid',
        'amount',
        'rem',
        'petty',
        'endingbal'
    ];
    private $except = ['trno', 'dateid'];
    private $acctg = [];
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
            'view' => 5095,
            'edit' => 5096,
            'new' => 5097,
            'save' => 5098,
            'delete' => 5099,
            'print' => 5100,
            'lock' => 5101,
            'unlock' => 5102,
            'post' => 5103,
            'unpost' => 5104,
            'additem' => 5105,
            'edititem' => 5106,
            'deleteitem' => 5107
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
        $rem = 4;
        $amount = 5;
        $postdate = 6;
        $listpostedby = 7;

        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'rem', 'amount', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];

        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$listdate]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';

        $cols[$rem]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[$amount]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $cols[$amount]['label'] = 'Beginning Balance';
        $cols[$rem]['label'] = 'Particular';
        $cols[$postdate]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[$listpostedby]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$postdate]['label'] = 'Post Date';

        $cols = $this->tabClass->delcollisting($cols);
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

        switch ($itemfilter) {
            case 'draft':
                $condition = ' and num.postdate is null ';
                break;
            case 'posted':
                $condition = ' and num.postdate is not null ';
                break;
        }

        $dateid = "left(head.dateid,10) as dateid";
        $yourref = 'head.yourref';
        if ($searchfilter == "") $limit = 'limit 150';
        $orderby =  "order by  dateid desc, docno desc";
        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['head.docno', 'head.rem', 'head.createby', 'head.editby', 'head.viewby', 'num.postedby'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        } else {
            $limit = 'limit 25';
        }

        $qry = "select head.trno,head.docno,head.rem,format(head.amount,2) as amount,$dateid, 'DRAFT' as status,
        head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate
        from " . $this->head . " as head left join " . $this->tablenum . " as num on num.trno=head.trno 
        left join " . $this->detail . " as detail on detail.trno = head.trno
        where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " $filtersearch
        group by  head.trno,head.docno,head.rem,head.amount,dateid,status,
        head.createby,head.editby,head.viewby,num.postedby, date(num.postdate)
        union all
        select head.trno,head.docno,head.rem,format(head.amount,2) as amount,$dateid,'POSTED' as status,
        head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate
        from " . $this->hhead . " as head left join " . $this->tablenum . " as num on num.trno=head.trno 
        left join " . $this->hdetail . " as detail on detail.trno = head.trno
        where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " $filtersearch
        group by  head.trno,head.docno,head.rem,head.amount,dateid,status,
        head.createby,head.editby,head.viewby,num.postedby, date(num.postdate)
        $orderby  $limit";
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
        $step1 = $this->helpClass->getFields(['btnnew', 'customer', 'dateid', 'yourref', 'cur', 'csrem', 'btnsave']);
        $step2 = $this->helpClass->getFields(['btnedit', 'customer', 'dateid', 'yourref', 'cur', 'csrem', 'btnsave']);
        $step3 = $this->helpClass->getFields(['btnaddaccount', 'db', 'cr', 'rem', 'btnstocksaveaccount', 'btnsaveaccount']);
        $step4 = $this->helpClass->getFields(['db', 'cr', 'rem', 'btnstocksaveaccount', 'btnsaveaccount']);
        $step5 = $this->helpClass->getFields(['btnstockdeleteaccount', 'btndeleteallaccount']);
        $step6 = $this->helpClass->getFields(['btndelete']);


        $buttons['help']['items'] = [
            'create' => ['label' => 'How to create New Document', 'action' => $step1],
            'edit' => ['label' => 'How to edit details from the header', 'action' => $step2],
            'additem' => ['label' => 'How to add account/s', 'action' => $step3],
            'edititem' => ['label' => 'How to edit account details', 'action' => $step4],
            'deleteitem' => ['label' => 'How to delete account/s', 'action' => $step5],
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
        $companyid = $config['params']['companyid'];
        $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrycntnumpicture', 'label' => 'Attachment', 'access' => 'view']];
        $obj = $this->tabClass->createtab($tab, []);

        $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
        if ($this->companysetup->getistodo($config['params'])) {
            $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrycntnumtodo', 'label' => 'To Do', 'access' => 'view']];
            $objtodo = $this->tabClass->createtab($tab, []);
            $return['To Do'] = ['icon' => 'fa fa-list', 'tab' => $objtodo];
        }

        $reflenishment = $this->othersClass->checkAccess($config['params']['user'], 5108);
        $reflenish = ['customform' => ['action' => 'customform', 'lookupclass' => 'replenish']];
        if ($reflenishment) {
            $return['REPLENISHMENT'] = ['icon' => 'fa fa-box', 'customform' => $reflenish];
        }
        return $return;
    }

    public function createTab($access, $config)
    {
        $columns = [
            'action',
            'empname',
            'acnotitle',
            'rem',
            'ref',
            'amount',
            'deduction',
            'balance', 'itemname'
        ];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $columns]];

        $stockbuttons = ['save', 'delete'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$action]['btns']['delete']['checkfield'] = 'isreplenish';
        $obj[0][$this->gridname]['columns'][$rem]['checkfield'] = 'isreplenish';
        $obj[0][$this->gridname]['columns'][$ref]['checkfield'] = 'isreplenish';
        $obj[0][$this->gridname]['columns'][$amount]['checkfield'] = 'isreplenish';
        $obj[0][$this->gridname]['columns'][$balance]['checkfield'] = 'isreplenish';
        $obj[0][$this->gridname]['columns'][$deduction]['checkfield'] = 'isreplenish';

        $obj[0][$this->gridname]['columns'][$rem]['style'] = 'width: 210px;whiteSpace: normal;min-width:210px;max-width:210px;';
        $obj[0][$this->gridname]['columns'][$ref]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$rem]['label'] = 'Particulars';
        $obj[0][$this->gridname]['columns'][$ref]['label'] = 'Reference #';
        $obj[0][$this->gridname]['columns'][$ref]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;';
        $obj[0][$this->gridname]['columns'][$ref]['readonly'] = false;
        $obj[0][$this->gridname]['columns'][$amount]['label'] = 'Additional Amount';
        $obj[0][$this->gridname]['columns'][$amount]['style'] = 'text-align:right;width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;';
        $obj[0][$this->gridname]['columns'][$balance]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$balance]['style'] = 'text-align:right;width:150px;whiteSpace: normal;min-width:150px;';
        $obj[0][$this->gridname]['columns'][$deduction]['style'] = 'text-align:right;width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;';

        // $obj[0][$this->gridname]['columns'][$acno]['type'] = 'lookup';
        // $obj[0][$this->gridname]['columns'][$acnogrid]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;';

        $obj[0][$this->gridname]['columns'][$empname]['readonly'] = false;


        $obj[0][$this->gridname]['descriptionrow'] = [];
        $obj[0][$this->gridname]['showtotal'] = false;
        $obj[0][$this->gridname]['label'] = ['ENTRIES'];
        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        $obj[0][$this->gridname]['totalfield'] = '';
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['addrow', 'deleteallitem', 'saveitem'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[1]['label'] = "DELETE";
        $obj[2]['label'] = "SAVE";
        return $obj;
    }

    public function createHeadField($config)
    {

        $companyid = $config['params']['companyid'];
        $systemtype = $this->companysetup->getsystemtype($config['params']);

        $fields = ['docno', 'rem'];

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'docno.label', 'Transaction#');


        $fields = ['dateid', 'petty', 'amount', 'endingbal'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col, 'petty.required', true);
        data_set($col2, 'amount.label', 'Beginning Balance');
        data_set($col2, 'amount.class', 'sbccsreadonly');
        data_set($col2, 'endingbal.class', 'sbccsreadonly');
        data_set($col2, 'endingbal.label', 'Ending Balance');


        return array('col1' => $col1, 'col2' => $col2);
    }



    public function createnewtransaction($docno, $params)
    {
        $center = $params['center'];
        $data = [];
        $data[0]['trno'] = 0;
        $data[0]['docno'] = $docno;
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['rem'] = '';
        $data[0]['amount'] = '0';
        $data[0]['petty'] = $this->coreFunctions->datareader("select petty as value from center where code = '" . $center . "'");
        $data[0]['endingbal'] = '0';
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
            } else {
                $t = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where trno = ? and center=? order by trno desc limit 1", [$trno, $center]);
                if ($t == '') {
                    $trno = 0;
                }
            }
            $config['params']['trno'] = $trno;
        } else {
            $this->othersClass->checkprofile('TRNO', $trno, $config);
        }
        $center = $config['params']['center'];

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
         left(head.dateid,10) as dateid, 
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem, format(head.amount,2) as amount, format(head.petty,2) as petty, format(head.endingbal,2) as endingbal";
        $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        where head.trno = ? and num.doc=? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno  
        where head.trno = ? and num.doc=? and num.center=? ";
        $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
        if (!empty($head)) {
            $detail = $this->openstock($trno, $config);
            $viewdate = $this->othersClass->getCurrentTimeStamp();
            $viewby = $config['params']['user'];
            $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }

            $hideobj = [];
            if ($this->companysetup->getistodo($config['params'])) {
                $btndonetodo = $this->othersClass->checkdonetodo($config, $tablenum);
                $hideobj = ['donetodo' => !$btndonetodo];
            }
            return  ['head' => $head, 'griddata' => ['inventory' => $detail], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg, 'hideobj' => $hideobj];
        } else {
            $head[0]['trno'] = 0;
            $head[0]['docno'] = '';
            return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed, either somebody already deleted the transaction or modified...'];
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

        if ($data['amount'] == 0) {
            $data['amount'] = $data['petty'];
        }

        $prevbal = $this->coreFunctions->datareader("select endingbal as value from htchead where date(dateid) < '" . $data['dateid'] . "' and trno <> " . $head['trno'] . " order by dateid desc limit 1");

        if ($prevbal != 0) {
            $data['amount'] = $prevbal;
            $data['endingbal'] = $prevbal;
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


        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        if ($this->othersClass->isposted($config)) {
            return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
        }
        //for htchead
        $qry = "insert into " . $this->hhead . "(trno,doc,docno,rem,amount,dateid,petty,endingbal,createdate,createby,editby,editdate,lockdate,lockuser,openby,users,viewby,viewdate)
        SELECT trno,doc,docno,rem,amount,dateid,petty,endingbal,createdate,createby,editby,editdate,lockdate,lockuser,openby,users,viewby,viewdate FROM " . $this->head . " as head 
        where head.trno=? limit 1";
        $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
        if ($posthead) {
            // for htcdetail
            $qry = "insert into " . $this->hdetail . "(trno,line,rem,ref,sortline,amount,deduction,balance,isreplenish,empname,acnoid,encodeddate,encodedby,editdate,editby)
            SELECT trno,line,rem,ref,sortline,amount,deduction,balance,isreplenish,empname,acnoid,encodeddate,encodedby,editdate,editby FROM " . $this->detail . " where trno =?";
            if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
                //update transnum
                $date = $this->othersClass->getCurrentTimeStamp();
                $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
                $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
                $this->coreFunctions->execqry("delete from " . $this->detail . " where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
                $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
                $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
                return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
            } else {
                $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
                return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting Detail'];
            }
        } else {
            return ['status' => false, 'msg' => 'Error on Posting Head'];
        }
    } //end function

    public function unposttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];
        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        $qry = "insert into " . $this->head . "(trno,doc,docno,rem,amount,dateid,petty,endingbal,createdate,createby,editby,editdate,lockdate,lockuser,openby,users,viewby,viewdate)
            select trno,doc,docno,rem,amount,dateid,petty,endingbal,createdate,createby,editby,editdate,lockdate,lockuser,openby,users,viewby,viewdate from " . $this->hhead . " as head 
            where head.trno=? limit 1";
        //head
        if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
            $qry = "insert into " . $this->detail . "(trno,line,rem,ref,sortline,amount,deduction,balance,isreplenish,empname,acnoid,encodeddate,encodedby,editdate,editby)
            select trno,line,rem,ref,sortline,amount,deduction,balance,isreplenish,empname,acnoid,encodeddate,encodedby,editdate,editby
            from " . $this->hdetail . " where trno=?";
            //stock
            if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
                $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
                $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from " . $this->hdetail . " where trno=?", "delete", [$trno]);
                $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
                return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
            } else {
                $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
                return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, detail problems...'];
            }
        } else {
            return ['status' => false, 'msg' => 'Error on Unposting Head'];
        }
    } //end function


    private function getdetailselect($config)
    {
        $qry = " head.trno,d.line,d.rem,d.ref,d.amount,d.deduction,0 as balance,
          d.sortline,'' as bgcolor,'' as errcolor,
          case when isreplenish = 1 then 'true' else 'false' end as isreplenish,
          d.empname,c.acnoname as acnotitle,c.acno,c.acnoid ";

        //case when isreplenish = 1 then 'true' else 'false' end as isreplenish 
        return $qry;
    }


    public function openstock($trno, $config)
    {
        $sqlselect = $this->getdetailselect($config);

        $qry = "select " . $sqlselect . " 
        from " . $this->detail . " as d
        left join " . $this->head . " as head on head.trno=d.trno
        left join coa as c on c.acnoid=d.acnoid
        where d.trno=?
        union all
        select " . $sqlselect . "  
        from " . $this->hdetail . " as d
        left join " . $this->hhead . " as head on head.trno=d.trno
        left join coa as c on c.acnoid=d.acnoid
        where d.trno=? order by line
        ";
        $detail = $this->coreFunctions->opentable($qry, [$trno, $trno]);
        $isposted = $this->othersClass->isposted2($trno, 'transnum');
        $htable = $this->head;
        if ($isposted) {
            $htable = $this->hhead;
        }

        $runningbal = 0;
        $begbal = $this->coreFunctions->getfieldvalue($htable, "amount", "trno=?", [$trno]);
        if ($begbal != 0) {
            $runningbal = $begbal;
        }

        foreach ($detail as $key => $value) {
            $value->amount = $this->othersClass->sanitizekeyfield('amt', $value->amount);
            $value->deduction = $this->othersClass->sanitizekeyfield('amt', $value->deduction);
            $runningbal = $runningbal + ($value->amount - $value->deduction);
            $value->amount = number_format($value->amount, 2);
            $value->deduction = number_format($value->deduction, 2);
            $value->balance = number_format($runningbal, 2);
        }

        return $detail;
    }


    public function openstockline($config)
    {
        $sqlselect = $this->getdetailselect($config);
        $trno = $config['params']['trno'];
        $line = $config['params']['line'];
        $qry = "select " . $sqlselect . " 
        from " . $this->detail . " as d
        left join " . $this->head . " as head on head.trno=d.trno
        left join coa as c on c.acnoid=d.acnoid
        where d.trno=? and d.line=? order by line";
        $detail = $this->coreFunctions->opentable($qry, [$trno, $line]);

        $isposted = $this->othersClass->isposted2($trno, 'transnum');
        $htable = $this->head;
        if ($isposted) {
            $htable = $this->hhead;
        }

        $runningbal = 0;
        $begbal = $this->coreFunctions->getfieldvalue($htable, "amount", "trno=?", [$trno]);
        if ($begbal != 0) {
            $runningbal = $begbal;
        }

        foreach ($detail as $key => $value) {
            $value->amount = $this->othersClass->sanitizekeyfield('amt', $value->amount);
            $value->deduction = $this->othersClass->sanitizekeyfield('amt', $value->deduction);
            $runningbal = $runningbal + ($value->amount - $value->deduction);
            $value->amount = number_format($value->amount, 2);
            $value->deduction = number_format($value->deduction, 2);
            $value->balance = number_format($runningbal, 2);
        }

        return $detail;
    } // end function

    public function stockstatus($config)
    {
        switch ($config['params']['action']) {
            case 'adddetail':
                return $this->additem('insert', $config);
                break;
            case 'addallitem':
                return $this->addallitem($config);
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
            case 'addrow':
                return $this->addrow($config);
                break;
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
                break;
        }
    }

    public function addrow($config)
    {
        $data = [];
        $trno = $config['params']['trno'];
        $data['line'] = 0;
        $data['trno'] = $trno;
        $data['empname'] = '';
        $data['acnotitle'] = '';
        $data['acnoid'] = '';
        $data['acno'] = '';
        $data['acnotitle'] = '';
        $data['rem'] = '';
        $data['ref'] = '';
        $data['amount'] = 0;
        $data['deduction'] = 0;
        $data['balance'] = 0;
        $data['bgcolor'] = 'bg-blue-2';
        return ['row' => $data, 'status' => true, 'msg' => 'New row added'];
    }



    public function stockstatusposted($config)
    {
        switch ($config['params']['action']) {
            case 'navigation':
                return $this->othersClass->navigatedocno($config);
                break;
            case 'donetodo':
                $tablenum = $this->tablenum;
                return $this->othersClass->donetodo($config, $tablenum);
                break;
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
                break;
        }
    }


    // public function updateperitem($config)
    // {
    //     $config['params']['data'] = $config['params']['row'];
    //     if ($config['params']['line'] != 0) {
    //         $this->additem('update', $config);
    //         $data = $this->openstockline($config);
    //         return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    //     } else {
    //         $exist = $this->coreFunctions->datareader("select rem as value from (select d.rem from tcdetail as d where d.trno ='" . $config['params']['data']['trno'] . "'
    //                 union all 
    //                select d.rem from htcdetail as d  where d.trno ='" . $config['params']['data']['trno'] . "' ) as a limit 1");
    //         if ($exist != '') {
    //             $stats['status'] = true;
    //             $stats['msg'] = 'Particular already exist. ';
    //         } else {
    //             $stats = $this->additem('insert', $config);
    //         }
    //         $data = $this->openstockline($config);

    //         if ($stats['status'] == true) {
    //             return ['row' => $stats['row'], 'status' => true, 'msg' => 'Successfully saved.'];
    //         } else {
    //             return ['row' => $data, 'status' => false, 'msg' => $stats['msg']];
    //         }
    //     }
    // }

    public function updateperitem($config)
    {
        $config['params']['data'] = $config['params']['row'];
        if ($config['params']['line'] != 0) {
            $this->additem('update', $config);
            $data = $this->openstockline($config);
            return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
        } else {
            $stats = $this->additem('insert', $config);
            $data = $this->openstockline($config);
            if ($stats['status'] == true) {
                return ['row' => $stats['row'], 'status' => true, 'msg' => 'Successfully saved.', 'reloadhead' => true, 'trno' => $config['params']['trno']];
            } else {
                return ['row' => $data, 'status' => false, 'msg' => $stats['msg']];
            }
        }
    }

    public function updateitem($config)
    {
        $msg1 = '';
        $msg2 = '';
        foreach ($config['params']['row'] as $key => $value) {
            $config['params']['data'] = $value;
            if ($value['line'] != 0) {
                $this->additem('update', $config);
            } else {
                $this->additem('insert', $config);
            }
        }
        $data = $this->openstock($config['params']['trno'], $config);
        $data2 = json_decode(json_encode($data), true);
        $isupdate = true;

        foreach ($data2 as $key => $value) {
            if ($data2[$key]['rem'] == "") {
                $data[$key]->errcolor = 'bg-red-2';
                $isupdate = false;
                $msg1 = 'Particular field is required. ';
            }
        }
        if ($isupdate) {
            return ['inventory' => $data, 'status' => true, 'msg' => $msg1 . ' Successfully saved.', 'reloadhead' => true, 'trno' => $config['params']['trno']];
        } else {

            return ['inventory' => $data, 'status' => true, 'msg' => 'Please check the following errors : ' . $msg1 . $msg2];
        }
    } //end function


    public function addallitem($config)
    {
        $error_msg = '';
        $status = true;
        foreach ($config['params']['row'] as $key => $value) {
            $config['params']['data'] = $value;
            $result = $this->additem('insert', $config);
            if (!$result['status']) {
                $error_msg .= ' ' . $result['msg'];
            }
        }
        if ($error_msg != '') {
            $msg = $error_msg;
            $status = false;
        } else {
            $msg = 'Successfully saved.';
        }
        $data = $this->openstock($config['params']['trno'], $config);
        return ['inventory' => $data, 'status' => $status, 'msg' => $msg, 'reloaddata' => true, 'trno' => $config['params']['trno']];
    } //end function


    // insert and update detail
    public function additem($action, $config)
    {
        $trno = $config['params']['trno'];
        $rem = $config['params']['data']['rem'];
        $ref = $config['params']['data']['ref'];
        $amount = $config['params']['data']['amount'];
        $deduction = $config['params']['data']['deduction'];
        $balance = $config['params']['data']['balance'];
        $empname = $config['params']['data']['empname'];

        $acno = $config['params']['data']['acno'];
        $acnoid = $config['params']['data']['acnoid'];
        $acnoname = $config['params']['data']['acnotitle'];


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
            $config['params']['line'] = $config['params']['data']['line'];
            $line = $config['params']['data']['line'];
            $config['params']['line'] = $line;
        }

        $data = [
            'trno' => $trno,
            'line' => $line,
            'rem' => $rem,
            'empname' => $empname,
            'acnoid' => $acnoid,
            'ref' => $ref,
            'amount' => $amount,
            'deduction' => $deduction
        ];
        foreach ($data as $key => $value) {
            $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        }
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $data['editdate'] = $current_timestamp;
        $data['editby'] = $config['params']['user'];
        $msg = '';
        $status = true;

        if ($action == 'insert') {
            $data['encodedby'] = $config['params']['user'];
            $data['encodeddate'] = $current_timestamp;
            $data['sortline'] =  $data['line'];
            if ($this->coreFunctions->sbcinsert($this->detail, $data) == 1) {
                $msg = 'Account was successfully added.';
                $this->logger->sbcwritelog($trno, $config, 'DETAIL', 'ADD - Line:' . $line . ' PARTICULAR:' . $rem . ' AMOUNT:' . $amount . ' REFERENCE:' . $ref);
                $row = $this->openstockline($config);
                $this->computeendbal($config);
                return ['row' => $row, 'status' => true, 'msg' => $msg, 'reloaddata' => true];
            } else {
                return ['status' => false, 'msg' => 'Add Account Failed'];
            }
        } elseif ($action == 'update') {
            $return = true;
            if ($this->coreFunctions->sbcupdate($this->detail, $data, ['trno' => $trno, 'line' => $line]) == 1) {
                $this->computeendbal($config);
            } else {
                $return = false;
            }
            return ['status' => $return, 'msg' => '', 'reloaddata' => true];
        }
    } // end function

    private function computeendbal($config)
    {
        $trno = $config['params']['trno'];
        $stock = $this->openstock($trno, $config);
        $isposted = $this->othersClass->isposted2($trno, 'transnum');
        $htable = $this->head;
        if ($isposted) {
            $htable = $this->hhead;
        }

        $runningbal = 0;
        $begbal = $this->coreFunctions->getfieldvalue($htable, "amount", "trno=?", [$trno]);
        if ($begbal != 0) {
            $runningbal = $begbal;
        }

        foreach ($stock as $key => $value) {
            $value->amount = $this->othersClass->sanitizekeyfield('amt', $value->amount);
            $value->deduction = $this->othersClass->sanitizekeyfield('amt', $value->deduction);
            $runningbal = $runningbal + ($value->amount - $value->deduction);
            $value->amount = number_format($value->amount, 2);
            $value->deduction = number_format($value->deduction, 2);
            $value->balance = number_format($runningbal, 2);
        }

        $this->coreFunctions->sbcupdate("tchead", ['endingbal' => $runningbal], ['trno' => $trno]);
    }

    public function deleteallitem($config)
    {
        $isallow = true;
        $trno = $config['params']['trno'];
        $data = $this->openstock($trno, $config);
        $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'DETAIL', 'DELETED ALL ENTRIES');
        return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
    }


    public function deleteitem($config)
    {
        $config['params']['trno'] = $config['params']['row']['trno'];
        $config['params']['line'] = $config['params']['row']['line'];
        $data = $this->openstockline($config);
        $trno = $config['params']['trno'];
        $line = $config['params']['line'];

        //if($config['params']['isreplenish'] == 1){}

        $qry = "delete from " . $this->detail . " where trno=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
        $this->logger->sbcwritelog($trno, $config, 'DETAIL', 'REMOVED - Line: ' . $line . ' PARTICULAR: ' . $data[0]->rem . ' REFERENCE: ' . $data[0]->ref);
        return ['status' => true, 'msg' => 'Successfully deleted entry.'];
    } // end function

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
        $this->logger->sbcviewreportlog($config);
        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }
} //end class
