<?php

namespace App\Http\Classes\modules\companyrules;

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
use App\Http\Classes\sqlquery;
use Exception;

class rg
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'COMPANY RULES AND GUIDELINES';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $sqlquery;
    private $logger;
    public $expirystatus = ['readonly' => false, 'show' => true, 'showdate' => true];
    public $tablenum = 'transnum';
    public $head = 'rghead';
    public $hhead = 'hrghead';
    public $stock = '';
    public $hstock = '';
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';
    public $htablelogs = 'htransnum_log';
    private $stockselect;
    public $dqty = '';
    public $hqty = '';
    public $damt = '';
    public $hamt = '';
    private $fields = ['trno', 'docno', 'dateid', 'rem'];
    private $otherfields = ['trno', 'sizeid', 'partid'];
    private $except = ['trno', 'dateid'];
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
        $this->sqlquery = new sqlquery;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 4969,
            'edit' => 4970,
            'new' => 4971,
            'save' => 4972,
            'delete' => 4973,
            'print' => 4974,
            'lock' => 4975,
            'unlock' => 4976,
            'post' => 4977,
            'unpost' => 4978,
            'adddoc' => 4982,
            'viewdoc' => 4983,
            'downloaddoc' => 4984,
            'deletedoc' => 4985
        );
        return $attrib;
    }


    public function createdoclisting()
    {
        $action = 0;
        $liststatus = 1;
        $listdocument = 2;
        $listdate = 3;
        $postdate = 4;

        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$postdate]['label'] = 'Post Date';
        return $cols;
    }

    public function paramsdatalisting($config)
    {
        return [];
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
        $addparams = '';
        $join = '';
        $hjoin = '';

        switch ($itemfilter) {
            case 'draft':
                $condition = ' and num.postdate is null ';
                break;
            case 'posted':
                $condition = ' and num.postdate is not null ';
                break;
        }


        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['head.docno', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }

        if ($searchfilter == "") $limit = 'limit 150';


        $qry = "select head.trno,head.docno,left(head.dateid,10) as dateid, 
                       'DRAFT' as status,head.createby,head.editby,head.viewby,num.postedby, 
                       date(num.postdate) as postdate  
                from " . $this->head . " as head 
                left join " . $this->tablenum . " as num on num.trno=head.trno 
                left join client as wh on wh.client=head.wh " . $join . " 
                where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? 
                      and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $addparams . " " . $filtersearch . "
                union all
                select head.trno,head.docno,left(head.dateid,10) as dateid,'POSTED' as status,
                head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate
                from " . $this->hhead . " as head 
                left join " . $this->tablenum . " as num on num.trno=head.trno 
                left join client as wh on wh.client=head.wh  " . $hjoin . " 
                where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? 
                      and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $addparams . " " . $filtersearch . "
                order by dateid desc, docno desc $limit";

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

        $buttons['others']['items'] = [
            'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
            'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
            'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
            'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
        ];



        if ($this->companysetup->getisshowmanual($config['params'])) {
            $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'pc', 'title' => 'PC_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
        }

        return $buttons;
    } // createHeadbutton

    public function createtab2($access, $config)
    {
        $tab = [];
        $obj = $this->tabClass->createtab($tab, []);
        return $obj;
    }


    public function createTab($access, $config)
    {
        $column = [];

        $headgridbtns = [];
        $tab = [
            $this->gridname => [
                'gridcolumns' => $column,
                'computefield' => [],
                'headgridbtns' => $headgridbtns
            ],
        ];

        $stockbuttons = [];
        $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function createHeadField($config)
    {
        $resellerid = $config['params']['resellerid'];
        $companyid = $config['params']['companyid'];
        $systemtype = $this->companysetup->getsystemtype($config['params']);
        $noeditdate = $this->othersClass->checkAccess($config['params']['user'], 4852);
        $fields = ['docno', 'dateid'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'docno.label', 'Transaction#');

        $fields = ['rem'];
        $col2 = $this->fieldClass->create($fields);

        return ['col1' => $col1, 'col2' => $col2];
    }



    public function createnewtransaction($docno, $params)
    {
        $data = [];
        $data[0]['trno'] = 0;
        $data[0]['docno'] = $docno;
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
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
         head.rem ";

        $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        where head.trno = ? and num.center = ?
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
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
        $companyid = $config['params']['companyid'];
        $head = $config['params']['head'];
        $data = [];
        $info = [];

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
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];

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
        $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno <? order by trno desc limit 1 ";
        $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);

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
        //for glhead
        $qry = "insert into " . $this->hhead . "(trno,doc,docno,dateid,rem,createdate,createby,editby,
                        editdate,lockdate,lockuser)
                SELECT head.trno,head.doc, head.docno,head.dateid as dateid,head.rem,
                       head.createdate,head.createby,head.editby,head.editdate,head.lockdate,head.lockuser
                FROM " . $this->head . " as head 
                left join transnum on transnum.trno=head.trno
                where head.trno=? limit 1";

        $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
        if ($posthead) {
            //update transnum
            $date = $this->othersClass->getCurrentTimeStamp();
            $data = ['postdate' => $date, 'postedby' => $config['params']['user'], 'statid' => 5];
            $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
            $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
            $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
            $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
            return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
        } else {
            return ['status' => false, 'msg' => 'An error occurred while posting head data.'];
        }
    } //end function

    public function unposttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];

        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        $qry = "insert into " . $this->head . "(trno,doc,docno,dateid,rem,createdate,createby,
                        editby,editdate,lockdate,lockuser)
                select head.trno, head.doc, head.docno,
                        head.dateid, head.rem,head.createdate,head.createby, head.editby, head.editdate, 
                        head.lockdate, head.lockuser
                from (" . $this->hhead . " as head 
                left join " . $this->tablenum . " as num on num.trno=head.trno) 
                left join client on client.client=head.client
                where head.trno=? limit 1";

        if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
            $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
            $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
            $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
            return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
        } else {
            return ['status' => false, 'msg' => 'An error occurred while unposting head data.'];
        }
    } //end function


    public function openstock($trno, $config)
    {
        $stock = [];
        return $stock;
    } //end function

    public function openstockline($config)
    {
        $stock = [];
        return $stock;
    } // end function

    public function stockstatus($config)
    {
    }

    public function stockstatusposted($config)
    {
        switch ($config['params']['action']) {
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

        return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
    }

    public function reportdata($config)
    {
        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }
} //end class
