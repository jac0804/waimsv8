<?php

namespace App\Http\Classes\modules\brgy;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\lookup\hrislookup;

class mn
{

    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'SUMMON';
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

    private $fields = ['trno', 'docno',  'dateid', 'clientname',  'bstype', 'address', 'contact', 'ownername', 'owneraddr', 'orderno',  'ourref', 'crno', 'conaddr', 'creditinfo', 'refdate', 'layref'];

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
            'load' => 5735,
            'view' => 5736,
            'edit' => 5737,
            'new' => 5738,
            'save' => 5739,
            'delete' => 5740,
            'print' => 5741,
            'lock' => 5742,
            'unlock' => 5743,
            'post' => 5744,
            'unpost' => 5745
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
        $fields = ['docno', 'clientname', 'address', 'contact'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'docno.label', 'Summon#');
        data_set($col1, 'clientname.label', 'Complainant');
        data_set($col1, 'clientname.class', 'csclientname sbccsreadonly');
        data_set($col1, 'address.class', 'csaddress sbccsreadonly');
        data_set($col1, 'contact.label', 'Contact No.#');
        data_set($col1, 'contact.class', 'cscontact sbccsreadonly');
        $fields = ['layref', 'ownername', 'owneraddr', 'orderno'];
        $col2 = $this->fieldClass->create($fields);

        data_set($col2, 'ownername.label', 'Respondent');
        data_set($col2, 'ownername.class', 'csownername sbccsreadonly');
        data_set($col2, 'owneraddr.label', 'Address');
        data_set($col2, 'owneraddr.class', 'csowneraddr sbccsreadonly');
        data_set($col2, 'orderno.label', 'Contact');
        data_set($col2, 'orderno.type', 'input');
        data_set($col2, 'orderno.class', 'csorderno sbccsreadonly');
        data_set($col2, 'layref.type', 'lookup');
        data_set($col2, 'layref.label', 'Brgy Case#');
        data_set($col2, 'layref.action', 'lookupbrgycomplaint');

        $fields = [['dateid', 'bstype'], ['refdate', 'ourref']];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'dateid.label', 'Date Summon');
        data_set($col3, 'bstype.label', 'Time');
        data_set($col3, 'ourref.label', 'Entered By');
        data_set($col3, 'ourref.class', 'csourref sbccsreadonly');
        data_set($col3, 'refdate.label', 'Date Complained');
        data_set($col3, 'refdate.class', 'csrefdate sbccsreadonly');

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
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
        select head.trno,head.docno,date_format(head.dateid,'%Y-%m-%d') as dateid,head.clientname,
        head.doc,head.createby, 'DRAFT' as status,ifnull(head.ownername,'') as planholder
        from lahead as head
        left join cntnum as num on num.trno = head.trno
        where num.doc = '$doc' $condition
        union all
        select  head.trno,head.docno,date_format(head.dateid,'%Y-%m-%d') as dateid,head.clientname,
        head.doc,head.createby,'POSTED' as status,ifnull(head.ownername,'') as planholder
        from glhead as head
        left join cntnum as num on num.trno = head.trno
        where num.doc = '$doc' $condition ";
        $data = $this->coreFunctions->opentable($query);
        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }
    public function createTab($config)
    {
        $fields = ['crno', 'conaddr', 'creditinfo'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'crno.label', 'FOR:');
        data_set($col1, 'crno.type', 'input');
        data_set($col1, 'crno.class', 'cscrno sbccsreadonly');
        data_set($col1, 'conaddr.label', 'T.D.P.O:');
        data_set($col1, 'conaddr.type', 'textarea');
        data_set($col1, 'conaddr.class', 'csconaddr sbccsreadonly');
        data_set($col1, 'conaddr.style', 'height:10em');
        data_set($col1, 'creditinfo.label', 'FACTS:');
        data_set($col1, 'creditinfo.class', 'cscreditinfo sbccsreadonly');
        $tab = [
            'multiinput1' => ['inputcolumn' => ['col1' => $col1,], 'label' => 'COMPLAINT']
        ];

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
        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'listplanholder'];
        foreach ($getcols as $key => $value) {
            $$value = $key;
        }
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$liststatus]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;';
        $cols[$listdocument]['label'] = 'Record No.';
        $cols[$listdocument]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[$listdate]['label'] = 'Date Summoned';
        $cols[$listdate]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$liststatus]['align'] = 'text-left';
        $cols[$listclientname]['label'] = 'Complainant';
        $cols[$listplanholder]['label'] = 'Respondent';
        return $cols;
    }
    public function loadheaddata($config)
    {
        $doc = $config['params']['doc'];
        $trno = $config['params']['trno'];
        $center = $config['params']['center'];
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
        $islocked = $this->othersClass->islocked($config);
        $isposted = $this->othersClass->isposted($config);
        $table = $this->head;
        $htable = $this->hhead;


        $qry = "select head.trno,head.docno, ifnull(head.clientname,'') as clientname, 
              ifnull(head.address,'') as address,ifnull(head.contact,'') as contact,head.dateid,
              ifnull(head.bstype,'') as bstype, ifnull(head.ownername,'') as ownername,
              ifnull(head.owneraddr,'') as owneraddr,ifnull(head.orderno,'') as orderno,
              ifnull(head.ourref,'') as ourref, ifnull(head.crno,'') as crno,ifnull(head.conaddr,'') as conaddr,
              ifnull(head.creditinfo,'') as creditinfo,date(head.refdate) as refdate,head.layref
              from $table as head
              left join $tablenum as num on num.trno = head.trno
              where num.doc = '$doc' and head.trno = ? 
              union all
              select head.trno,head.docno, ifnull(head.clientname,'') as clientname, 
              ifnull(head.address,'') as address,ifnull(head.contact,'') as contact,head.dateid,
              ifnull(head.bstype,'') as bstype, ifnull(head.ownername,'') as ownername,
              ifnull(head.owneraddr,'') as owneraddr,ifnull(head.orderno,'') as orderno,
              ifnull(head.ourref,'') as ourref, ifnull(head.crno,'') as crno,ifnull(head.conaddr,'') as conaddr,
              ifnull(head.creditinfo,'') as creditinfo,date(head.refdate) as refdate,head.layref
              from $htable as head
              left join $tablenum as num on num.trno = head.trno
              where num.doc = '$doc' and head.trno = ? ";
        $head = $this->coreFunctions->opentable($qry, [$trno, $trno]);
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
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['clientname'] = '';
        $data[0]['bstype'] = '';
        $data[0]['address'] = '';
        $data[0]['contact'] = '';
        $data[0]['ownername'] = '';
        $data[0]['owneraddr'] = '';
        $data[0]['orderno'] = '';
        $data[0]['ourref'] = '';

        $data[0]['crno'] = '';
        $data[0]['conaddr'] = '';
        $data[0]['creditinfo'] = '';
        $data[0]['refdate'] =  $this->othersClass->getCurrentDate();
        $data[0]['layref'] = '';
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
                $data[$key] = $this->othersClass->sanitizekeyfield($key, $head[$key], '');
            }
        }

        if ($isupdate) {
            $prev_layref = $this->coreFunctions->getfieldvalue('lahead',  "layref", "trno=?", [$head['trno']]);

            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];

            if ($prev_layref != $head['layref']) {
                if (!empty($prev_layref)) {
                    $prevtrno = $this->coreFunctions->getfieldvalue('glhead', "trno", "docno=?", [$prev_layref]);
                    if ($prevtrno) {
                        $this->coreFunctions->sbcupdate('glhead',  ['isfinish' => 0], ['trno' => $prevtrno]);
                    }
                }
                if (!empty($head['layref'])) {
                    $newtrno = $this->coreFunctions->getfieldvalue('glhead', "trno", "docno=?", [$head['layref']]);
                    if ($newtrno) {
                        $this->coreFunctions->sbcupdate('glhead',  ['isfinish' => 1], ['trno' => $newtrno]);
                    }
                }
            }
            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
        } else {

            $data['doc'] = $config['params']['doc'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $this->coreFunctions->sbcinsert($this->head, $data);
            $complainttrno = $this->coreFunctions->getfieldvalue('glhead', "trno", "docno=?", [$head['layref']]);
            if ($complainttrno) {
                $this->coreFunctions->sbcupdate('glhead', ['isfinish' => 1], ['trno' => $complainttrno]);
            }

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
        $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
        $this->logger->sbcdel_log($trno, $config, $docno);
        return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
    } //end function


    public function reportsetup($config)
    {
        $trno = $config['params']['trno'];
        $doc = $config['params']['doc'];
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

        $data = app($this->companysetup->getreportpath($config['params']))->generateResult($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }
}
