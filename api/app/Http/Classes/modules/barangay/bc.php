<?php

namespace App\Http\Classes\modules\barangay;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\lookup\hrislookup;

class bc
{

    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'BUSINESS CLEARANCE';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    public $expirystatus = ['readonly' => false, 'show' => true, 'showdate' => true];
    public $tablenum = 'cntnum';
    public $head = 'lahead';
    public $hhead = 'glhead';
    public $stock = 'lastock';
    public $hstock = 'glstock';
    public $detail = 'ladetail';
    public $hdetail = 'gldetail';
    public $tablelogs = 'table_log';
    public $htablelogs = 'htable_log';
    public $tablelogs_del = 'del_table_log';

    private $fields = [
        'trno',
        'docno',
        'dateid',
        'amount',
        'client',
        'clientname',
        'bstype',
        'address',
        'contact',
        'rem',
        'ownertype',
        'ownername',
        'owneraddr',
        'crno',
        'trnxtype'
    ];

    private $except = ['trno'];
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
            'load' => 5289,
            'view' => 5290,
            'edit' => 5291,
            'new' => 5292,
            'save' => 5293,
            'delete' => 5294,
            'print' => 5295,
            'lock' => 5296,
            'unlock' => 5297,
            'post' => 5298,
            'unpost' => 5299
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
        return $buttons;
    }
    public function createHeadField($config)
    {
        $fields = ['docno', 'client', 'clientname', 'address', 'bstype'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.label', 'Business ID');
        data_set($col1, 'clientname.label', 'Business Name');
        data_set($col1, 'clientname.class', 'sbccsreadonly');
        data_set($col1, 'client.action', 'lookupbusinessclr');
        data_set($col1, 'address.class', 'sbccsreadonly');
        data_set($col1, 'bstype.class', 'sbccsreadonly');
        $fields = ['contact',  'ownertype', 'ownername', 'owneraddr', 'dateid'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'contact.label', 'Contact No.#');
        data_set($col2, 'owneraddr.class', 'sbccsreadonly');
        data_set($col2, 'contact.class', 'sbccsreadonly');
        data_set($col2, 'ownertype.class', 'sbccsreadonly');
        data_set($col2, 'ownername.class', 'sbccsreadonly');
        data_set($col2, 'dateid.label', 'Clearance Date');
        $fields = ['amount', 'crno', 'trnxtype', 'rem'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'amount.label', 'Fee');
        data_set($col3, 'crno.label', 'Receipt No');
        data_set($col3, 'trnxtype.label', 'MP Reference');
        data_set($col3, 'trnxtype.type', 'input');
        data_set($col3, 'rem.label', 'Remarks / Notes');
        $fields = ['picture'];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'picture.table', 'la_picture');
        data_set($col4, 'picture.fieldid', 'trno');
        data_set($col4, 'picture.lookupclass', 'client');
        data_set($col4, 'picture.folder', 'bc');
        data_set($col4, 'picture.action', 'client');
        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }
    public function loaddoclisting($config)
    {
        $itemfilter = $config['params']['itemfilter'];
        $doc = $config['params']['doc'];
        $center = $config['params']['center'];
        $condition = '';
        $searchfilter = $config['params']['search'];
        $filtersearch = "";
        switch ($itemfilter) {
            case 'draft':
                $condition = ' and num.postdate is null';
                break;
            case 'posted':
                $condition = ' and num.postdate is not null ';
                break;
        }
        $query = "
        select head.trno,head.docno,head.dateid,cl.clientname,
        head.doc,head.createby, 'DRAFT' as status
        from lahead as head
        left join cntnum as num on num.trno = head.trno
        left join client as cl on cl.client=head.client
        where num.doc = '$doc' $condition
        union all
        select  head.trno,head.docno,head.dateid,cl.clientname,
        head.doc,head.createby,'POSTED' as status
        from glhead as head
        left join cntnum as num on num.trno = head.trno
        left join client as cl on cl.clientid=head.clientid
        where num.doc = '$doc' $condition ";
        $data = $this->coreFunctions->opentable($query);
        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }
    public function createTab($config)
    {
        $tab = [];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        return $obj;
    }
    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }
    public function createdoclisting($config)
    {
        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname'];
        foreach ($getcols as $key => $value) {
            $$value = $key;
        }
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$liststatus]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;';
        $cols[$listdocument]['label'] = 'Record No.';
        $cols[$listdocument]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[$listdate]['label'] = 'Clearance Date';
        $cols[$listdate]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$liststatus]['align'] = 'text-left';
        $cols[$listclientname]['label'] = 'Business Name';
        return $cols;
    }
    public function loadheaddata($config)
    {
        $doc = $config['params']['doc'];
        $trno = $config['params']['trno'];
        $center = $config['params']['center'];

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
        $islocked = $this->othersClass->islocked($config);
        $isposted = $this->othersClass->isposted($config);
        $query = "
        select head.trno,head.docno,head.dateid,FORMAT(head.amount,2) as amount,
              ifnull(cl.client,'') as client, ifnull(cl.clientname,'') as clientname, 
              ifnull(head.address,'') as address, ifnull(head.bstype,'') as bstype,cl.clientid,
              ifnull(head.contact,'') as contact, ifnull(head.rem,'') as rem,
              ifnull(head.ownertype,'') as ownertype, ifnull(head.ownername,'') as ownername,
              ifnull(head.owneraddr,'') as owneraddr,ifnull(head.crno,'') as crno,ifnull(head.checker,'') as checker,cp.picture,ifnull(head.trnxtype,'') as trnxtype
        from lahead as head
        left join cntnum as num on num.trno = head.trno
        left join client as cl on cl.client=head.client
        left join cntnum_picture as cp on cp.trno=head.trno
        where num.doc = '$doc' and head.trno = ?
        union all
        select head.trno,head.docno,head.dateid,FORMAT(head.amount,2) as amount,
               ifnull(cl.client,'') as client, ifnull(cl.clientname,'') as clientname, 
               ifnull(head.address,'') as address, ifnull(head.bstype,'') as type,cl.clientid,
                ifnull(head.contact,'') as contact, ifnull(head.rem,'') as rem,
              ifnull(head.ownertype,'') as ownertype, ifnull(head.ownername,'') as ownername,
              ifnull(head.owneraddr,'') as owneraddr,ifnull(head.crno,'') as crno,ifnull(head.checker,'') as checker,cp.picture,ifnull(head.trnxtype,'') as trnxtype
        from glhead as head
        left join cntnum as num on num.trno = head.trno
        left join client as cl on cl.clientid=head.clientid
         left join cntnum_picture as cp on cp.trno=head.trno
        where num.doc = '$doc' and head.trno = ? ";
        $head = $this->coreFunctions->opentable($query, [$trno, $trno]);
        if (!empty($head)) {
            $viewdate = $this->othersClass->getCurrentTimeStamp();
            $viewby = $config['params']['user'];
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }
            $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
            return  ['head' => $head, 'griddata' => ['inventory' => []], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg];
        } else {
            $head[0]['trno'] = 0;
            $head[0]['docno'] = '';
            return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
        }
    }
    public function createnewtransaction($docno, $params)
    {
        $data = [];
        $data[0]['trno'] = 0;
        $data[0]['docno'] = $docno;
        $data[0]['amount'] = '0';
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['client'] = '';
        $data[0]['clientname'] = '';
        $data[0]['bstype'] = '';
        $data[0]['address'] = '';
        $data[0]['contact'] = '';
        $data[0]['rem'] = '';
        $data[0]['ownertype'] = '';
        $data[0]['ownername'] = '';
        $data[0]['owneraddr'] = '';
        $data[0]['crno'] = '';
        $data[0]['trnxtype'] = '';
        return $data;
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
                $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], '');
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
    }


    public function posttrans($config)
    {
        return $this->othersClass->posttranstock($config);
    } //end function

    public function unposttrans($config)
    {
        return $this->othersClass->unposttranstock($config);
    } //end function

    public function deletetrans($config)
    {
        $trno = $config['params']['trno'];
        $doc = $config['params']['doc'];
        $table = $config['docmodule']->tablenum;
        $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
        $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
        $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);
        // $this->deleteallitem($config);
        $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
        $this->logger->sbcdel_log($trno, $config, $docno);
        return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
    } //end function
}
