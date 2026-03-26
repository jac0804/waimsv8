<?php

namespace App\Http\Classes\modules\masterfile;

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

class tl
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'T.R.U Ledger';
    public $gridname = 'accounting';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $sqlquery;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
    public $tablenum = 'transnum';
    public $head = 'client';
    public $tablelogs = 'client_log';
    public $tablelogs_del = 'del_client_log';
    public $tagging = "istru";
    public $prefix = 'TL';

    private $fields = [
        'client',
        'clientname',
        'addr',
        'start',
        'istru',
        'make',
        'color',
        'motorno',
        'plateno'
    ];
    private $except = ['clientid'];
    private $blnfields = [];
    private $clinfo = [
        'fname',
        'lname',
        'mname',
        'chassisno',
        'sidecarno'
    ];
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
            'load' => 5610,
            'view' => 5611,
            'edit' => 5612,
            'new' => 5613,
            'save' => 5614,
            'delete' => 5615,
            'print' => 5616
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
        $fields = ['client', 'lname', 'fname', 'mname', 'clientname', 'addr'];
        $col1 = $this->fieldClass->create($fields);
        // data_set($col1, 'client.label', 'Barangay Member');
        // data_set($col1, 'client.action', 'lookupbrgyclient');

        data_set($col1, 'clientname.class', 'cspurpose sbccsreadonly');
        data_set($col1, 'address.label', 'Address');
        data_set($col1, 'address.class', 'csaddressno sbccsreadonly');

        data_set($col1, 'clientname.label', 'Full Name');
        $fields = ['make', 'motorno', 'chassisno', 'color', 'sidecarno', 'plateno'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'chassisno.label', 'Chassis No.');
        data_set($col2, 'plateno.label', 'Plate No.');
        $fields = ['start'];
        $col3 = $this->fieldClass->create($fields);

        data_set($col3, 'start.label', 'Register Date');
        $fields = ['picture'];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'picture.lookupclass', 'client');
        data_set($col4, 'picture.folder', 'brgytru');
        data_set($col4, 'picture.table', 'client');
        data_set($col4, 'picture.fieldid', 'clientid');

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
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
        $action = 0;
        $listclient = 1;
        $listclientname = 2;
        $listaddr = 2;
        $getcols = ['action', 'listclient', 'listclientname',  'listaddr'];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols = $this->tabClass->delcollisting($cols);
        return $cols;
    }
    public function loaddoclisting($config)
    {
        $search = $config['params']['search'];
        $limit = "limit " . $this->companysetup->getmasterlimit($config['params']);
        $grp = "";
        $searchby = isset($config['params']['doclistingparam']['selectprefix']) ? $config['params']['doclistingparam']['selectprefix'] : '';
        $searchfield = ['client.client', 'client.clientname'];
        if ($search != "") {
            $limit = "";
        }

        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }

        if ($searchby != '') {
            $grp = " group by client.clientid,client.client,client.clientname";
        }

        $qry = "select client.clientid,client.client,concat(info.lname,', ',info.fname,' ',info.mname) as clientname,
                info.lname
        from client 
        left join clientinfo as info on info.clientid = client.clientid 
        where client.istru =1 " .  $filtersearch .  $grp . "  
        order by client " . $limit;
        $data = $this->coreFunctions->opentable($qry);
        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }
    public function loadheaddata($config)
    {
        $doc = $config['params']['doc'];
        $clientid = $config['params']['clientid'];
        $center = $config['params']['center'];
        if ($clientid == 0) {
            $clientid = $this->othersClass->readprofile($doc, $config);
            if ($clientid == 0) {
                $clientid = $this->coreFunctions->datareader("select clientid as value from client where isbrgy=1 and center=? order by clientid desc limit 1", [$center]);
            }
            $config['params']['clientid'] = $clientid;
        } else {
            $this->othersClass->checkprofile($doc, $clientid, $config);
        }
        $center = $config['params']['center'];
        $head = [];
        $query = "
        select client.clientid,client.client,concat(info.lname,', ',info.fname,' ',info.mname) as clientname,
        client.addr,client.clientid,date(client.start) as start, 
        client.plateno,client.make,client.motorno,client.color,info.chassisno,info.sidecarno,
        info.lname,info.fname,info.mname,client.picture
        
        from client 
        left join clientinfo as info on info.clientid = client.clientid
        where client.clientid = ? and client.istru = 1";
        $head = $this->coreFunctions->opentable($query, [$clientid]);
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
            $head[0]['trno'] = 0;
            $head[0]['docno'] = '';
            return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
        }
    }
    public function newclient($config)
    {
        $data = [];
        $data[0]['clientid'] = 0;
        $data[0]['client'] = $config['newclient'];
        $data[0]['start'] = $this->othersClass->getCurrentDate();
        $data[0]['clientname'] = '';
        $data[0]['mname'] = '';
        $data[0]['fname'] = '';
        $data[0]['lname'] = '';
        $data[0]['istru'] = '1';
        $data[0]['iscustomer'] = '1';
        $data[0]['addr'] = '';

        $data[0]['carno'] = '';
        $data[0]['chassisno'] = '';
        $data[0]['sidecarno'] = '';
        $data[0]['motorno'] = '';
        $data[0]['plateno'] = '';
        $data[0]['color'] = '';
        $data[0]['make'] = '';
        $data[0]['picture'] = '';

        return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
    }
    public function getlastclient($pref)
    {
        $length = strlen($pref);
        $return = '';
        if ($length == 0) {
            $return = $this->coreFunctions->datareader('select client as value from client where  istru=1 order by client desc limit 1');
        } else {
            $return = $this->coreFunctions->datareader('select client as value from client where  istru=1 and left(client,?)=? order by client desc limit 1', [$length, $pref]);
        }
        return $return;
    }
    public function updatehead($config, $isupdate)
    {
        $head = $config['params']['head'];
        $center = $config['params']['center'];
        $data = [];
        $clientinfo = [];
        $companyid = $config['params']['companyid'];
        if ($isupdate) {
            unset($this->fields[0]);
        }
        $clientid = 0;
        $msg = '';
        foreach ($this->fields as $key) {
            if (array_key_exists($key, $head)) {
                $data[$key] = $head[$key];
                if (!in_array($key, $this->except)) {
                    $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], '', $companyid);
                } //end if
            }
        }

        foreach ($this->clinfo as $key) {
            if (!in_array($key, $this->except)) {
                $clientinfo[$key] = $head[$key];
                $clientinfo[$key] = $this->othersClass->sanitizekeyfield($key, $clientinfo[$key]);
            } //end if    
        }

        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];

        if ($isupdate) {
            $this->coreFunctions->sbcupdate('client', $data, ['clientid' => $head['clientid']]);
            $clientid = $head['clientid'];
            array_push($this->fields, 'client');
            //info
            $exist = $this->coreFunctions->getfieldvalue("clientinfo", "clientid", "clientid=?", [$clientid], '', true);
            if ($exist == 0) {
                $clientinfo['clientid'] = $clientid;
                $this->coreFunctions->sbcinsert("clientinfo", $clientinfo);
            } else {
                $clientinfo['editdate'] = $this->othersClass->getCurrentTimeStamp();
                $clientinfo['editby'] = $config['params']['user'];
                $this->coreFunctions->sbcupdate('clientinfo', $clientinfo, ['clientid' => $head['clientid']]);
            }
        } else {

            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $data['istru'] = 1;
            $data['iscustomer'] = 1;
            $data['center'] = $center;
            $data['clientname'] = $head['lname'] . ', ' . $head['fname'] . ' ' . $head['mname'];
            $exist = $this->coreFunctions->getfieldvalue("client", "clientname", "clientname = ? and istru =1", [$head['clientname']]);
            if (strlen(($exist)) != 0) {
                return ['status' => false, 'msg' => 'This member already exist.', 'clientid' => $clientid];
            } else {
                $clientid = $this->coreFunctions->insertGetId('client', $data);
                if (!empty($clientinfo)) {
                    $clientinfo['clientid'] = $clientid;
                    $this->coreFunctions->sbcinsert("clientinfo", $clientinfo);
                }

                $this->logger->sbcwritelog($clientid, $config, 'CREATE', $clientid . ' - ' . $head['client'] . ' - ' . $head['clientname']);
            }
        }

        return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid];
    } // end function
}
