<?php

namespace App\Http\Classes\modules\s966bcd74e8482da1569c6b839996c0dd;

/*
use Session;*/

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Classes\stockClass;
use App\Http\Classes\othersClass;
use App\Http\Classes\clientClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\headClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\builder\lookupClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\mobile\modules\inventoryapp\inventory;
use Exception;
use Throwable;
use Session;

use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;


class adashboard
{
    private $othersClass;
    private $coreFunctions;
    private $headClass;
    private $logger;
    private $lookupClass;
    private $companysetup;
    private $config = [];
    private $sqlquery;
    private $tabClass;
    private $fieldClass;

    public function __construct()
    {
        $this->othersClass = new othersClass;
        $this->coreFunctions = new coreFunctions;
        $this->headClass = new headClass;
        $this->logger = new Logger;
        $this->lookupClass = new lookupClass;
        $this->companysetup = new companysetup;
        $this->sqlquery = new sqlquery;
        $this->tabClass = new tabClass;
        $this->fieldClass = new txtfieldClass;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 5223,
        );
        return $attrib;
    }

    public function sbc($params)
    {
        $doc = strtolower($params['doc']);
        $type = strtolower($params['moduletype']);
        $classname = __NAMESPACE__ . '\\modules\\' . $type . '\\' . $doc;
        try {
            $this->config['classname'] = $classname;
            $this->config['docmodule'] = new $classname();
        } catch (Exception $e) {
            echo $e;
            return $this;
        }
        $this->config['params'] = $params;
        if (isset($this->config['params']['logintype'])) {
            if ($this->config['params']['logintype'] == '62608e08adc29a8d6dbc9754e659f125') {
                $access = $this->othersClass->getportalaccess($params['user']);
            } else {
                $access = $this->othersClass->getAccess($params['user']);
            }
        } else {
            $access = $this->othersClass->getAccess($params['user']);
        }
        $this->config['access'] = json_decode(json_encode($access), true);
        $this->config['mattrib'] = $this->config['docmodule']->getAttrib();
        if ($this->companysetup->getrestrictip($params)) {
            $ipaccess = $this->config['access'][0]['attributes'][3722]; //restrict ip access
            if ($ipaccess == 1) {
                $this->config['allowlogin'] = $this->othersClass->checkip($params);
                if (!$this->config['allowlogin']) {
                    $this->config['msg'] = 'RESTRICTED IP, pls inform admin';
                }
                $this->coreFunctions->LogConsole("Your IP - '" . $params['ip'] . "'");
            } else {
                $this->config['allowlogin'] = true;
            }
        }

        $istimechecking = $this->othersClass->istimechecking($params);
        if ($istimechecking['status']) {
            $this->config['loginexpired'] = $istimechecking['loginexpired'];
        }
        return $this;
    }

    public function checksecurity($accessid)
    {
        if (isset($this->config['mattrib'][$accessid])) {
            $id = $this->config['mattrib'][$accessid];

            $companyid = $this->config['params']['companyid'];
            if ($companyid == 49) { //hotmix

                if ($this->config['params']['doc'] == 'RR') {
                    if (isset($this->config['params']['action'])) {
                        if ($this->config['params']['action'] == 'getposummary') $id = $this->config['mattrib']['save'];
                        if ($this->config['params']['action'] == 'getpodetails') $id = $this->config['mattrib']['save'];
                    }
                }
            }

            $this->config['verifyaccess'] = $this->config['access'][0]['attributes'][$id - 1];
            if ($this->config['verifyaccess'] == 0) {
                $this->config['return'] = ['status' => 'denied', 'msg' => 'Invalid Access'];
            }
        } else {
            $this->coreFunctions->sbclogger('Undefined ' . $accessid . ' ' . $this->config['params']['doc'] . ' id: ' . $this->config['params']['id']);
            $this->config['return'] = ['status' => 'denied', 'msg' => 'Undefined ' . $accessid . ' ' . $this->config['params']['doc']];
        }

        return $this;
    }

    public function execute()
    {
        if (isset($this->config['allowlogin'])) {
            if (!$this->config['allowlogin']) {
                return response()->json(['status' => 'ipdenied', 'msg' => 'Sorry, Please contact your Network Administrator', 'xx' => $this->config], 200);
            }
        }

        return response()->json($this->config['return'], 200);
    } // end function

    public function loadaform($config)
    {
        $this->config = $config;
        $this->dashboardwaims();
        return $this->config['return'];
    }

    public function dashboardwaims()
    {
        ini_set('max_execution_time', -1);

        $this->approveapv();

        $sorting = ['qcard', 'actionlist', 'dailynotif', 'overview', 'sbcgraph', 'sbclist'];
        $this->config['return'] = [
            'status' => true,
            'msg' => 'Loaded Success',
            'obj' => $this->config,
            'sorting' => $sorting
        ];
    } //end function

    public function dashboardclienttable() {} //end function

    public function approveapv()
  {
    $center = $this->config['params']['center'];
    $getcols = ['action', 'docno', 'dateid','clientname'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['btns']['view']['lookupclass'] = 'jumptableentry';
    $wh = $this->companysetup->getwh($this->config['params']);
    $fields = [];
    $col1 = $this->fieldClass->create($fields);
    $paramsdata = $this->coreFunctions->opentable("SELECT 'XXX' as ourref");

    $qry = "select head.trno,cntnum.doc, head.docno, date(head.dateid) as dateid, d.clientname as clientname,'../../ledgergrid/s966bcd74e8482da1569c6b839996c0dd/postingapv' as url
    from lahead as head left join cntnum on cntnum.trno=head.trno  left join client as d on d.client = head.client where head.doc='PV' 
    and head.lockdate is not null and cntnum.center = ?  limit 20";
    $data = $this->coreFunctions->opentable($qry,[$center]);
    $this->config['sbclist']['approveapv'] = ['cols' => $cols, 'data' => $data, 'title' => 'APV APPROVAL', 'txtfield' => ['col1' => $col1], 'paramsdata' => $paramsdata[0]];
  }

    
}
