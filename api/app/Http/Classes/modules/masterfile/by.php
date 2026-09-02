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

class by
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'EMPLOYEE INFORMATION';
    public $gridname = 'accounting';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $sqlquery;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
    public $head = 'client';
    public $tablelogs = 'client_log';
    public $tablelogs_del = 'del_client_log';
    public $tagging = "isbrgyemp";
    public $prefix = 'BY';

    private $fields = [
        'client',
        'clientname',
        'isbrgyemp',
        'start',
        'bday',
        'position',
        'sex',
        'hired',
        'mobile',
        'enddate',
        'rem',
        'picture'
    ];
    private $except = ['clientid'];
    private $blnfields = [];
    private $clinfo = [
        'lname',
        'num',
        'fname',
        'mname',
        'addressno',
        'contactno',
        'tin',
        'sname'
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
            'view' => 5685,
            'edit' => 5686,
            'new' => 5687,
            'save' => 5688,
            'delete' => 5689,
            'print' => 5690
        );

        return $attrib;
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

    public function paramsdatalisting($config)
    {
        return [];
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
            $grp = " group by client.clientid,client.client,client.clientname,client.bday,
                     info.lname";
        }

        $qry = "select client.clientid,client.client,concat(info.lname,', ',info.fname,' ',info.mname) as clientname,client.bday,
                info.lname,info.addressno as addr
        from client 
        left join clientinfo as info on info.clientid = client.clientid 
        where client.isbrgyemp =1 " .  $filtersearch .  $grp . "  
        order by client " . $limit;
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
        $buttons['others']['items'] = [
            'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
            'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
            'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
            'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
        ];
        if ($this->companysetup->getisshowmanual($config['params'])) {
            $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'customer', 'title' => 'CUSTOMER_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
        }

        return $buttons;
    }

    public function createTab($access, $config)
    {
        $tab = [];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        return $obj;
    }


    public function createHeadField($config)
    {
        $fields = ['client', 'lname', 'clientname', 'addressno', 'sex', 'tin'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.label', 'Barangay member');
        data_set($col1, 'clientname.label', 'Full Name');
        data_set($col1, 'clientname.class', 'sbccsreadonly');
        data_set($col1, 'addressno.label', 'Address');

        $fields = ['num', 'fname', 'contactno', 'position', 'sname', 'enddate'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'num.label', 'Record No.#');
        data_set($col2, 'num.required', false);
        data_set($col2, 'num.class', 'csnum sbccsreadonly');
        data_set($col2, 'contactno.label', 'Contact No.#');
        data_set($col2, 'sname.label', 'E.C.P:');
        data_set($col2, 'enddate.label', 'Valid until');
        data_set($col2, 'sname.required', false);

        $fields = ['start', 'mname', 'bday',  'hired', 'mobile', 'rem'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'start.label', 'Record Date');
        data_set($col3, 'hired.label', 'Date Start');
        data_set($col3, 'mobile.label', 'ECP Contact #');
        data_set($col3, 'rem.label', 'Remarks/Notes');

        $fields = ['picture'];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'picture.lookupclass', 'client');
        data_set($col4, 'picture.folder', 'brgyimg');
        data_set($col4, 'picture.table', 'client');
        data_set($col4, 'picture.fieldid', 'clientid');
        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function newclient($config)
    {
        $data = [];
        $data[0]['clientid'] = 0;
        $data[0]['client'] = $config['newclient'];
        $data[0]['clientname'] = '';
        $data[0]['num'] = '';
        $data[0]['fname'] = '';
        $data[0]['lname'] = '';
        $data[0]['mname'] = '';
        $data[0]['start'] = $this->othersClass->getCurrentDate();
        $data[0]['contactno'] = '';
        $data[0]['bday'] = $this->othersClass->getCurrentDate();
        $data[0]['position'] = '';
        $data[0]['sex'] = '';
        $data[0]['tin'] = '';
        $data[0]['hired'] = $this->othersClass->getCurrentDate();
        $data[0]['sname'] = '';
        $data[0]['mobile'] = '';
        $data[0]['addressno'] = '';
        $data[0]['enddate'] =  $this->othersClass->getCurrentTimeStamp();
        $data[0]['rem'] = '';
        $data[0]['picture'] = '';
        $data[0]['isbrgyemp'] = 1;

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
                $clientid = $this->coreFunctions->datareader("select clientid as value from client where isbrgyemp=1 and center=? order by clientid desc limit 1", [$center]);
            }
            $config['params']['clientid'] = $clientid;
        } else {
            $this->othersClass->checkprofile($doc, $clientid, $config);
        }
        $center = $config['params']['center'];
        $head = [];
        $qryselect = "select client.clientid, client.client,concat(info.lname,', ',info.fname,' ',info.mname) as clientname,
                     info.lname,info.fname,date(client.start) as start, info.mname,
                     ifnull(client.position,'') as position,client.sex,
                     ifnull(info.tin,'') as tin, date(client.hired) as hired,ifnull(info.sname,'') as sname,
                     ifnull(client.mobile,'') as mobile,ifnull(info.addressno,'') as addressno,
                     ifnull(client.enddate,'') as enddate,ifnull(client.rem,'') as rem,client.picture,
                     ifnull(info.contactno,'') as contactno,client.bday,right(client.client,4) as num";
        $qry = $qryselect . " from client
        left join clientinfo as info on info.clientid = client.clientid
        where client.clientid = ? and client.isbrgyemp = 1";
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
        $data = [];
        $clientinfo = [];
        $companyid = $config['params']['companyid'];

        $dateTables = ['client', 'clientinfo'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

        if ($isupdate) {
            unset($this->fields[0]);
        }
        $clientid = 0;
        $msg = '';
        foreach ($this->fields as $key) {
            if (array_key_exists($key, $head)) {
                $data[$key] = $head[$key];
                if (!in_array($key, $this->except)) {
                    $data[$key] = $this->othersClass->sanitizekeyfieldFast($key, $data[$key], $lookups);
                } //end if
            }
        }

        foreach ($this->clinfo as $key) {
            if (!in_array($key, $this->except)) {
                $clientinfo[$key] = $head[$key];
                $clientinfo[$key] = $this->othersClass->sanitizekeyfieldFast($key, $clientinfo[$key], $lookups);
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
            $data['center'] = $center;
            $exist = $this->coreFunctions->getfieldvalue("client", "clientname", "clientname = ? and isbrgyemp =1", [$head['clientname']]);
            if (strlen(($exist)) != 0) {
                return ['status' => false, 'msg' => 'This employee already exist.', 'clientid' => $clientid];
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

    public function createtab2($access, $config)
    {
        // // standard attachment tab
        $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entryclientpicture', 'label' => 'Attachment', 'access' => 'view']];
        $attach = $this->tabClass->createtab($tab, []);
        $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $attach];
        return $return;
    }
    public function createtabbutton($config)
    {
        return [];
    }
    public function stockstatusposted($config)
    {
        $action = $config['params']['action'];
        switch ($action) {
            case 'navigation':
                return $this->othersClass->navigatedocno($config);
                break;
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
                break;
        }
    }

    public function getlastclient($pref)
    {
        $length = strlen($pref);
        $return = '';
        if ($length == 0) {
            $return = $this->coreFunctions->datareader('select client as value from client where  isbrgyemp=1 order by client desc limit 1');
        } else {
            $return = $this->coreFunctions->datareader('select client as value from client where  isbrgyemp=1 and left(client,?)=? order by client desc limit 1', [$length, $pref]);
        }
        return $return;
    }

    public function deletetrans($config)
    {
        $clientid = $config['params']['clientid'];
        $client = $this->coreFunctions->getfieldvalue('client', 'client', 'clientid=?', [$clientid]);
        $qry = "select clientid as value from client where clientid=? and isbrgyemp=1 order by clientid desc limit 1 ";
        $clientid2 = $this->coreFunctions->datareader($qry, [$clientid]);
        $this->coreFunctions->execqry('delete from client where clientid=?', 'delete', [$clientid]);
        $this->coreFunctions->execqry('delete from clientinfo where clientid=?', 'delete', [$clientid]);
        $this->logger->sbcdel_log($clientid, $config, $client);
        $this->othersClass->deleteattachments($config); // attachment delete
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
