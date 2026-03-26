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

class bg
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Barangay Ledger';
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
    public $tagging = "isbrgy";
    public $prefix = 'BG';

    private $fields = [
        'client',
        'clientname',
        'isbrgy',
        'bday',
        'sex',
        'start',
        'religion',
        'picture',
        'addr',
        'province',
        'mobile',
        'rem',
        'year'
    ];
    private $except = ['clientid'];
    private $blnfields = [];
    private $clinfo = [
        'bplace',
        'lname',
        'hhold',
        'num',
        'fname',
        'settlertype',
        'citizenship',
        'mname',
        'civilstatus',
        'height',
        'weight',
        'isdp',
        'addressno',
        'attainment1',
        'employer',
        'sname',
        'attainment2',
        'street',
        'occupation1',
        'skill1',
        'bday2',
        'skill2',
        'occupation2',
        'rvoter',
        'precintno',
        'tin',
        'sssgsis',
        'names',
        'address',
        'relation',
        'contactno'
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
            'view' => 5259,
            'edit' => 5260,
            'new' => 5261,
            'save' => 5262,
            'delete' => 5263,
            'print' => 5264
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
                     info.lname,info.bplace  ,info.hhold";
        }

        $qry = "select client.clientid,client.client,client.clientname,client.bday,
                info.lname,info.bplace as addr,info.hhold
        from client 
        left join clientinfo as info on info.clientid = client.clientid 
        where client.isbrgy =1 " .  $filtersearch .  $grp . "  
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
        $fields = ['addressno', 'addr', 'street', 'province', 'mobile', 'year'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'addressno.label', 'Address No.#');
        data_set($col1, 'province.label', 'Provincial Address');
        data_set($col1, 'province.type', 'input');
        data_set($col1, 'year.label', 'Yrs. of residency in this brgy');
        $fields = ['attainment1', 'occupation1', 'skill1', 'employer', 'rvoter', 'precintno', 'tin', 'sssgsis'];
        $col2 = $this->fieldClass->create($fields);

        data_set($col2, 'employer.label', 'Employer/Address');
        $fields = ['lblgrossprofit', 'sname', 'attainment2', 'bday2', 'occupation2', 'skill2'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'sname.label', 'Name of Spouse');
        data_set($col3, 'lblgrossprofit.label', 'Spouse Information:');
        data_set($col3, 'lblgrossprofit.style', 'font-weight:bold; font-size:13px;');

        $fields = ['lblcostuom', 'rem'];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'lblcostuom.label', 'Remarks/Notes');
        data_set($col4, 'lblcostuom.style', 'font-weight:bold; font-size:13px;');
        data_set($col4, 'rem.label', '');

        $fields = ['lbltotalkg', 'names', 'address'];
        $col5 = $this->fieldClass->create($fields);
        data_set($col5, 'lbltotalkg.label', 'IN CASE OF EMERGENCY, PLEASE NOTIFY');
        data_set($col5, 'lbltotalkg.style', 'font-weight:bold; font-size:12px;');


        $fields = ['lblshipping', 'relation', 'contactno'];
        $col6 = $this->fieldClass->create($fields);
        data_set($col6, 'lblshipping.label', '.');
        data_set($col6, 'lblshipping.style', 'font-size:1px;');
        data_set($col6, 'contactno.label', 'Contact No.#');

        $tab = [
            'multiinput1' => ['inputcolumn' => ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4], 'label' => 'PROFILE'],
            'multiinput2' => ['inputcolumn' => ['col5' => $col5, 'col6' => $col6], 'label' => 'OTHER INFO']
        ];
        $tab['tableentry'] = ['action' => 'tableentry', 'lookupclass' => 'entryhouseholdd', 'label' => 'HOUSEHOLD MEMBER'];
        $tab['tableentry2'] = ['action' => 'tableentry', 'lookupclass' => 'issuedclearance', 'label' => 'CLEARANCE ISSUED'];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        return $obj;
    }


    public function createHeadField($config)
    {
        $fields = ['client', 'lname', 'clientname', 'bday', 'bplace', 'hhold'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.label', 'Barangay member');
        data_set($col1, 'clientname.label', 'Full Name');
        data_set($col1, 'clientname.class', 'sbccsreadonly');

        $fields = ['num', 'fname', 'sex', 'citizenship', 'settlertype'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'num.label', 'Rec.No.#');
        data_set($col2, 'num.required', false);
        data_set($col2, 'num.class', 'csnum sbccsreadonly');

        $fields = ['start', 'mname', 'civilstatus', 'religion', ['height', 'weight']];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'start.label', 'Register Date');
        data_set($col3, 'civilstatus.lookupclass', 'lookupbcivilstat');
        data_set($col3, 'civilstatus.addedparams', ['sname', 'attainment2', 'bday2', 'occupation2', 'skill2']);
        data_set($col3, 'civilstatus.required', true);
        data_set($col3, 'civilstatus.error', false);

        $fields = ['picture', 'isdp'];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'isdp.label', 'Register Only');
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
        $data[0]['bday'] = $this->othersClass->getCurrentDate();
        $data[0]['bplace'] = '';
        $data[0]['citizenship'] = '';
        $data[0]['sex'] = '';
        $data[0]['start'] = $this->othersClass->getCurrentDate();
        $data[0]['civilstatus'] = '';
        $data[0]['height'] = '';
        $data[0]['weight'] = '';
        $data[0]['mname'] = '';
        $data[0]['religion'] = '';
        $data[0]['num'] = '';
        $data[0]['fname'] = '';
        $data[0]['settlertype'] = '';
        $data[0]['lname'] = '';
        $data[0]['hhold'] = '';
        $data[0]['isbrgy'] = '1';
        $data[0]['iscustomer'] = '1';
        $data[0]['isdp'] = '0';
        $data[0]['picture'] = '';

        //profile
        $data[0]['addressno'] = '';
        $data[0]['addr'] = '';
        $data[0]['province'] = '';
        $data[0]['mobile'] = '';
        $data[0]['attainment1'] = '';
        $data[0]['employer'] = '';
        $data[0]['sname'] = '';
        $data[0]['attainment2'] = '';
        $data[0]['rem'] = '';

        $data[0]['street'] = '';
        $data[0]['year'] = '';
        $data[0]['occupation1'] = '';
        $data[0]['skill1'] = '';
        $data[0]['bday2'] = $this->othersClass->getCurrentDate();
        $data[0]['skill2'] = '';
        $data[0]['occupation2'] = '';


        $data[0]['rvoter'] = '';
        $data[0]['precintno'] = '';
        $data[0]['tin'] = '';
        $data[0]['sssgsis'] = '';

        $data[0]['names'] = '';
        $data[0]['address'] = '';
        $data[0]['relation'] = '';
        $data[0]['contactno'] = '';

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
                $clientid = $this->coreFunctions->datareader("select clientid as value from client where isbrgy=1 and center=? order by clientid desc limit 1", [$center]);
            }
            $config['params']['clientid'] = $clientid;
        } else {
            $this->othersClass->checkprofile($doc, $clientid, $config);
        }
        $center = $config['params']['center'];
        $head = [];

        $qryselect = "select client.clientid, client.client,client.bday,client.sex,
                    info.bplace,info.lname,info.hhold,info.num,info.fname,info.settlertype,info.citizenship,
                    right(client.client,4) as num,date(client.start) as start, 
                    client.religion,info.mname,info.civilstatus,info.height,info.weight,
                    case when info.isdp=0 then '0' else '1' end as isdp,client.picture,
                    ifnull(info.addressno,'') as addressno,ifnull(client.addr,'') as addr,ifnull(client.mobile,'') as mobile,
                    ifnull(info.attainment1,'') as attainment1,ifnull(info.employer,'') as employer,
                    ifnull(info.sname,'') as sname, ifnull(info.attainment2,'') as attainment2,
                    ifnull(client.rem,'') as rem, ifnull(client.province,'') as province,
                    ifnull(client.year,'') as year, ifnull(info.street,'') as street,
                    ifnull(info.occupation1,'') as occupation1, ifnull(info.skill1,'') as skill1,
                    date(info.bday2) as bday2,ifnull(info.skill2,'') as skill2, ifnull(info.occupation2,'') as occupation2,
                    ifnull(info.rvoter,'') as rvoter, ifnull(info.precintno,'') as precintno,
                    ifnull(info.tin,'') as tin,  ifnull(info.sssgsis,'') as sssgsis,
                    ifnull(info.names,'') as names, ifnull(info.address,'') as address,
                    ifnull(info.relation,'') as relation, ifnull(info.contactno,'') as contactno,
                    concat(info.lname,', ',info.fname,' ',info.mname) as clientname
                    ";
        $qry = $qryselect . " from client
        left join clientinfo as info on info.clientid = client.clientid
        where client.clientid = ? and client.isbrgy = 1";
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
        // $data['dlock'] = $this->othersClass->getCurrentTimeStamp();

        if ($isupdate) {
            // if ($companyid == 55) { //AFLI Lending
            //     $fullName = trim($head['lname'] . ', ' . $head['fname'] . ' ' . $head['mname']);
            //     $data['clientname'] = $fullName;
            // }

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
            // if ($companyid == 55) { //AFLI Lending
            //     $fullName = trim($head['lname'] . ', ' . $head['fname'] . ' ' . $head['mname']);
            //     $data['clientname'] = $fullName;
            // }
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $data['isbrgy'] = 1;
            $data['iscustomer'] = 1;
            $data['center'] = $center;
            $exist = $this->coreFunctions->getfieldvalue("client", "clientname", "clientname = ? and isbrgy =1", [$head['clientname']]);
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
            $return = $this->coreFunctions->datareader('select client as value from client where  isbrgy=1 order by client desc limit 1');
        } else {
            $return = $this->coreFunctions->datareader('select client as value from client where  isbrgy=1 and left(client,?)=? order by client desc limit 1', [$length, $pref]);
        }
        return $return;
    }

    public function deletetrans($config)
    {
        $clientid = $config['params']['clientid'];
        $client = $this->coreFunctions->getfieldvalue('client', 'client', 'clientid=?', [$clientid]);
        $qry = "select clientid as value from client where clientid=? and isbrgy=1 order by clientid desc limit 1 ";
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

        // if ($companyid == 40) { // cdo
        //     $dataparams = $config['params']['dataparams'];
        //     if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
        //     if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
        //     if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);
        // }

        $data = app($this->companysetup->getreportpath($config['params']))->generateResult($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }
} //end class
