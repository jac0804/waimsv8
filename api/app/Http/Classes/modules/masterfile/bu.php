<?php

namespace App\Http\Classes\modules\masterfile;

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

class bu
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'BUSINESS LEDGER';
    public $gridname = 'accounting';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $sqlquery;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
    public $head = 'client';
    public $prefix = 'BU';
    public $tablelogs = 'client_log';
    public $tablelogs_del = 'del_client_log';
    private $stockselect;

    private $fields = [
        'client',
        'picture',
        'clientname',
        'bstyle',
        'brgy',
        'area',
        'addr',
        'contact',
        'acquireddate',
        'building',
        'owner',
        'addr2',
        'rem',
        'isbusiness',
        'isallowliquor',
        'clientpref'
    ];

    private $except = ['clientid'];
    private $blnfields = ['isbusiness', 'isallowliquor'];
    private $acctg = [];
    public $showfilteroption = false;
    public $showfilter = false;
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
        $this->sqlquery = new sqlquery;
        $this->reporter = new SBCPDF;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 5283,
            'edit' => 5284,
            'new' => 5285,
            'save' => 5286,
            'delete' => 5287,
            'print' => 5288,
            'load' => 5282
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $getcols = ['action', 'listclient', 'listclientname', 'listaddr'];

        foreach ($getcols as $key => $value) {
            $$value = $key;
        }

        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';

        // $cols[4]['label'] = 'Truck Type';


        return $cols;
    }

    public function loaddoclisting($config)
    {
        $date1 = $config['params']['date1'];
        $date2 = $config['params']['date2'];
        $itemfilter = $config['params']['itemfilter'];
        $doc = $config['params']['doc'];
        $center = $config['params']['center'];

        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['client.client', 'client.clientname', 'client.addr'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }

        $qry = "select clientid,client,clientname,addr
                from client 
                where isbusiness = 1 $filtersearch
                order by client";

        $data = $this->coreFunctions->opentable($qry);
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
            'logs',
            'edit',
            'backlisting',
            'toggleup',
            'toggledown'
        );
        $buttons = $this->btnClass->create($btns);
        return $buttons;
    } // createHeadbutton

    public function createTab($access, $config)
    {
        $tab['tableentry'] = ['action' => 'tableentry', 'lookupclass' => 'ownermembertab', 'label' => 'OWNER MEMBER'];
        // $tab['viewprojref'] = ['action' => 'tableentry', 'lookupclass' => 'liquortab', 'label' => 'LIQUOR PROFILE'];
        $tab['tableentry2'] = ['action' => 'tableentry', 'lookupclass' => 'clearancetab', 'label' => 'CLEARANCE ISSUED'];

        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        // $obj[0]['lookupclass']  = 'viewwhinv';
        return $obj;
    }

    public function createHeadField($config)
    {
        $systype = $this->companysetup->getsystemtype($config['params']);

        $fields = ['client', 'clientname', 'bstyle', ['brgy', 'street'], 'area', 'addr'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.label', 'Business I.D.');
        data_set($col1, 'clientname.label', 'Business Name');
        data_set($col1, 'bstyle.label', 'Business Type');
        data_set($col1, 'brgy.type', 'input');
        data_set($col1, 'brgy.label', 'Address No.');
        data_set($col1, 'street.type', 'lookup');
        data_set($col1, 'street.action', 'lookupstreet');
        data_set($col1, 'street.class', 'sbccsreadonly');
        data_set($col1, 'street.label', 'Street Code');
        data_set($col1, 'street.readonly', true);
        data_set($col1, 'area.type', 'input');
        data_set($col1, 'area.class', 'sbccsreadonly');
        data_set($col1, 'area.label', 'Street');
        data_set($col1, 'addr.class', 'sbccsreadonly');

        $fields = [['acquireddate', 'clientpref'], 'building', 'owner', 'addr2', 'contact', 'rem'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'building.label', 'Owner Type');
        data_set($col2, 'building.required', false);
        data_set($col2, 'addr2.label', 'Owner Address');
        data_set($col2, 'contact.label', 'Contact No.');

        $fields = ['picture', 'isallowliquor'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'picture.lookupclass', 'client');
        data_set($col3, 'picture.folder', 'business');
        data_set($col3, 'picture.table', 'client');
        data_set($col3, 'picture.fieldid', 'clientid');

        $fields = [];
        $col4 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }


    public function newclient($config)
    {
        $data = [];
        $data[0]['clientid'] = 0;
        $data[0]['client'] = $config['newclient'];
        $data[0]['clientname'] = '';
        $data[0]['bstyle'] = '';
        $data[0]['brgy'] = '';
        $data[0]['area'] = '';
        $data[0]['addr'] = '';
        $data[0]['contact'] = '';
        $data[0]['acquireddate'] = $this->othersClass->getCurrentDate();
        $data[0]['clientpref'] = '';
        $data[0]['building'] = '';
        $data[0]['owner'] = '';
        $data[0]['addr2'] = '';
        $data[0]['rem'] = '';
        $data[0]['picture'] = '';
        $data[0]['isbusiness'] = 1;
        $data[0]['iscustomer'] = 1;
        $data[0]['isallowliquor'] = '0';

        return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
    }


    public function loadheaddata($config)
    {
        $doc = $config['params']['doc'];
        $clientid = $config['params']['clientid'];
        $center = $config['params']['center'];
        if ($clientid == 0) {
            $clientid = $this->othersClass->readprofile($doc, $config);
            if ($clientid == 0) {
                $clientid = $this->coreFunctions->datareader("select clientid as value from client where isbusiness=1 and center=? order by clientid desc limit 1", [$center]);
            }
            $config['params']['clientid'] = $clientid;
        } else {
            $this->othersClass->checkprofile($doc, $clientid, $config);
        }
        $center = $config['params']['center'];
        $head = [];
        $fields = 'client.clientid';
        foreach ($this->fields as $key => $value) {
            $fields = $fields . ',client.' . $value;
        }


        $qryselect = "select " . $fields;
        $qry = $qryselect . " , st.code as street
        from client  
        left join street as st on st.street = client.area
        where client.clientid = ? ";

        $head = $this->coreFunctions->opentable($qry, [$clientid]);
        if (!empty($head)) {
            foreach ($this->blnfields as $key => $value) {
                if ($head[0]->$value) {
                    $head[0]->$value = "1";
                } else
                    $head[0]->$value = "0";
            }
            $viewdate = $this->othersClass->getCurrentTimeStamp();
            $viewby = $config['params']['user'];
            $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['clientid' => $clientid]);
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }
            return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid']];
        } else {
            $head[0]['clientid'] = 0;
            $head[0]['client'] = '';
            $head[0]['clientname'] = '';
            return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
        }
    }

    public function updatehead($config, $isupdate)
    {
        $head = $config['params']['head'];
        $center = $config['params']['center'];
        $companyid = $config['params']['companyid'];
        $data = [];

        if ($isupdate) {
            unset($this->fields[0]);
            unset($this->fields[1]);
        }
        $clientid = 0;
        $msg = '';
        foreach ($this->fields as $key) {
            if (array_key_exists($key, $head)) {
                $data[$key] = $head[$key];
                if (!in_array($key, $this->except)) {
                    $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], $config['params']['doc'], $companyid);
                } //end if    
            }
        }

        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        $data['addr'] = $data['brgy'] . ' ' . $data['area'];

        if ($isupdate) {
            $this->coreFunctions->sbcupdate('client', $data, ['clientid' => $head['clientid']]);
            $clientid = $head['clientid'];
            array_push($this->fields, 'client');
        } else {
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            // $data['isbusiness'] = 1;
            $data['center'] = $center;
            $clientid = $this->coreFunctions->insertGetId('client', $data);
            $this->logger->sbcwritelog($clientid, $config, 'CREATE', $clientid . ' - ' . $head['client'] . ' - ' . $head['clientname']);
        }
        return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid];
    } // end function

    public function getlastclient($pref)
    {
        $length = strlen($pref);
        $return = '';
        if ($length == 0) {
            $return = $this->coreFunctions->datareader('select client as value from client where  isbusiness=1 order by client desc limit 1');
        } else {
            $return = $this->coreFunctions->datareader('select client as value from client where  isbusiness=1 and left(client,?)=? order by client desc limit 1', [$length, $pref]);
        }
        return $return;
    }

    public function deletetrans($config)
    {
        $systemtype = $this->companysetup->getsystemtype($config['params']);
        $clientid = $config['params']['clientid'];
        $doc = $config['params']['doc'];
        $client = $this->coreFunctions->getfieldvalue('client', 'client', 'clientid=?', [$clientid]);
        $qry = "select trno as value from lahead where client=?
            union all 
            select trno as value from glhead where clientid=? limit 1";
        $count = $this->coreFunctions->datareader($qry, [$client, $clientid]);
        if (($count != '')) {
            return ['clientid' => $clientid, 'status' => false, 'msg' => 'Cannot delete; there is already a transaction.'];
        }

        $qry = "select clientid as value from client where clientid<? and isbusiness=1 order by clientid desc limit 1 ";
        $clientid2 = $this->coreFunctions->datareader($qry, [$clientid]);
        $this->coreFunctions->execqry('delete from client where clientid=?', 'delete', [$clientid]);
        $this->logger->sbcdel_log($clientid, $config, $client);
        return ['clientid' => $clientid2, 'status' => true, 'msg' => 'Successfully deleted.'];
    } //end function

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

        $data = app($this->companysetup->getreportpath($config['params']))->generateResult($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }
} //end class
