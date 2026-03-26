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

class infra
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Infrastructure Ledger';
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
    public $tagging = "isinfra";
    public $prefix = 'IF';

    private $fields = [
        'client',
        'clientname',
        'addr',
        'isinfra',
        'infratype',
        'regdate',
        'picture'
    ];
    private $except = ['clientid'];
    private $blnfields = [];
    private $clinfo = [
        'sentence1',
        'sentence2',
        'sentence3',
        'bullet1',
        'bullet2',
        'bullet3',
        'bullet4',
        'bullet5',
        'bullet6',
        'bullet7'
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
            'view' => 5606,
            'edit' => 5630,
            'new' => 5631,
            'save' => 5632,
            'delete' => 5633,
            'print' => 5634,
            'load' => 5635,
            'change' => 5636
        );

        return $attrib;
    }

    public function createdoclisting($config)
    {
        $action = 0;
        $listclient = 1;
        $listclientname = 2;
        $listaddr = 2;
        $getcols = ['action', 'listclient', 'listinfratype',  'listregdate'];
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
            $grp = " group by client.clientid,client.client,client.clientname,client.infratype,client.regdate
                     ";
        }

        $qry = "select client.clientid,client.client,client.clientname,client.infratype,
                date(client.regdate) as regdate
        from client 
        where client.isinfra =1 " .  $filtersearch .  $grp . "  
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

    
    public function createHeadField($config)
    {
      $fields = ['client', 'clientname', 'addr', 'infratype'];
      $col1 = $this->fieldClass->create($fields);
      data_set($col1, 'client.lookupclass', 'lookupinfracode');
      data_set($col1, 'client.action', 'lookupinfracode');
      data_set($col1, 'clientname.type', 'ctextarea');

      $fields = ['regdate'];
      $col2 = $this->fieldClass->create($fields);
    

      $fields = [];
      $col3 = $this->fieldClass->create($fields);
      

      $fields = ['picture'];
      $col4 = $this->fieldClass->create($fields);
      
      return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function createTab($access, $config)
    {
        $fields = ['sentence1', 'bullet1', 'bullet2'];
        $col1 = $this->fieldClass->create($fields);
        
        // data_set($col1, 'sentence1.style', 'width: 100%; min-width: 300px; max-width: 500px; white-space: normal; text-align: left;');
        // data_set($col1, 'bullet1.style', 'width: 100%; min-width: 300px; max-width: 500px; white-space: normal; text-align: left;');
        // data_set($col1, 'bullet2.style', 'width: 100%; min-width: 300px; max-width: 500px; white-space: normal; text-align: left;');
        
        $fields = ['sentence2','bullet3', 'bullet4'];
        $col2 = $this->fieldClass->create($fields);

        // data_set($col2, 'sentence2.style', 'width: 100%; min-width: 300px; max-width: 500px; white-space: normal; text-align: left;');
        // data_set($col2, 'bullet3.style', 'width: 100%; min-width: 300px; max-width: 500px; white-space: normal; text-align: left;');
        // data_set($col2, 'bullet4.style', 'width: 100%; min-width: 300px; max-width: 500px; white-space: normal; text-align: left;');
        
        $fields = ['sentence3','bullet5', 'bullet6','bullet7'];
        $col3 = $this->fieldClass->create($fields);
        
        // data_set($col3, 'sentence3.style', 'width: 100%; min-width: 300px; max-width: 500px; white-space: normal; text-align: left;');
        // data_set($col3, 'bullet5.style', 'width: 100%; min-width: 300px; max-width: 500px; white-space: normal; text-align: left;');
        // data_set($col3, 'bullet6.style', 'width: 100%; min-width: 300px; max-width: 500px; white-space: normal; text-align: left;');
        // data_set($col3, 'bullet7.style', 'width: 100%; min-width: 300px; max-width: 500px; white-space: normal; text-align: left;');
        

        
        $tab = [
            'multiinput1' => ['inputcolumn' => ['col1' => $col1,'col2' => $col2,'col3' => $col3], 'label' => 'LIST OF BULLETS'],
            'multiinput2' => ['inputcolumn' => ['col2' => $col2], 'label' => 'TRANSACTION LISTS']
        ];
        // $tab['tableentry'] = ['action' => 'tableentry', 'lookupclass' => 'entryhouseholdd', 'label' => 'HOUSEHOLD MEMBER'];
        // $tab['tableentry2'] = ['action' => 'tableentry', 'lookupclass' => 'issuedclearance', 'label' => 'CLEARANCE ISSUED'];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        return $obj;
        // $tab = [];
        // return $tab;
    }



    public function newclient($config)
    {
        $data = [];
        $data[0]['clientid'] = 0;
        $data[0]['client'] = $config['newclient'];
        $data[0]['clientname'] = '';
        $data[0]['addr'] = '';
        
            
        
        $data[0]['infratype'] = '';
        $data[0]['regdate'] = $this->othersClass->getCurrentDate();

        
        $data[0]['picture'] = '';

        $data[0]['sentence1'] = '';
        $data[0]['sentence2'] = '';
        $data[0]['sentence3'] = '';
        $data[0]['bullet1'] = '';
        $data[0]['bullet2'] = '';
        $data[0]['bullet3'] = '';
        $data[0]['bullet4'] = '';
        $data[0]['bullet5'] = '';
        $data[0]['bullet6'] = '';
        $data[0]['bullet7'] = '';
        

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
                $clientid = $this->coreFunctions->datareader("select clientid as value from client where isinfra=1 and center=? order by clientid desc limit 1", [$center]);
            }
            $config['params']['clientid'] = $clientid;
        } else {
            $this->othersClass->checkprofile($doc, $clientid, $config);
        }
        $center = $config['params']['center'];
        $head = [];

        $qryselect ="
                    select client.clientid, client.client,client.clientname,client.addr,client.infratype,client.regdate,client.picture,
                    ifnull(info.sentence1,'') as sentence1,ifnull(info.sentence2,'') as sentence2,ifnull(info.sentence3,'') as sentence3,
                    ifnull(info.bullet1,'') as bullet1,
                    ifnull(info.bullet2,'') as bullet2,
                    ifnull(info.bullet3,'') as bullet3,
                    ifnull(info.bullet4,'') as bullet4,
                    ifnull(info.bullet5,'') as bullet5,
                    ifnull(info.bullet6,'') as bullet6,
                    ifnull(info.bullet7,'') as bullet7
                    ";
        $qry = $qryselect . " from client
        left join clientinfo as info on info.clientid = client.clientid
        where client.clientid = ? and client.isinfra = 1";
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
        $date = $this->othersClass->getCurrentTimeStamp();
        $date = date_create($date);
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
          
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $data['isinfra'] = 1;
            $data['center'] = $center;
            $clientinfo['sentence1'] = 'This is to certify that '.$data['clientname'].' is applying for barangay clearance for loading/unloading of their leased space located at '.$data['addr'].' within the jurisdiction of this barangay. The undersigned interposes no objection to the application provided that the applicant shall:';
            
            $clientinfo['sentence2'] = 'This certification is issued upon the request of the above named applicant for the purpose herein stated only.';
            $sentencedate = date_format($date,"jS").' day of '.date_format($date,"F").', '.date_format($date,"Y");
            
            $clientinfo['sentence3'] ='Issued this '.$sentencedate.' at Barangay Dona Imelda, Quezon City. Metro Manila.';

            $clientinfo['bullet1'] = 'Meet all the necessary requirements set forth by all local government agencies concerned;';
            $clientinfo['bullet2'] = 'Observe safety measures during the loading/unloading works;';
            $clientinfo['bullet3'] = 'Maintain cleanliness and orderliness during the loading/unloading works;';
            $clientinfo['bullet4'] = 'Observe working hours to avoid disturbance among the neighboring business establishment;';
            $clientinfo['bullet5'] = 'Repair and restore all damages that may result from their said project at the expense of the contractor / applicant;';
            $clientinfo['bullet6'] = 'Acquire the necessary permits from various government offices concerned;';
            $clientinfo['bullet7'] = '';
            $exist = $this->coreFunctions->getfieldvalue("client", "clientname", "clientname = ? and isinfra =1", [$head['clientname']]);
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
            $return = $this->coreFunctions->datareader('select client as value from client where  isinfra=1 order by client desc limit 1');
        } else {
            $return = $this->coreFunctions->datareader('select client as value from client where  isinfra=1 and left(client,?)=? order by client desc limit 1', [$length, $pref]);
        }
        return $return;
    }

    public function deletetrans($config)
    {
        $clientid = $config['params']['clientid'];
        $client = $this->coreFunctions->getfieldvalue('client', 'client', 'clientid=?', [$clientid]);
        $qry = "select clientid as value from client where clientid=? and isinfra=1 order by clientid desc limit 1 ";
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
