<?php

namespace App\Http\Classes\modules\hrisentry;

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
use App\Http\Classes\lookup\hrislookup;

class entryexaminees
{

    private $fieldClass;
    private $tabClass;
    public $modulename = 'Examinees';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'examinees';
    public $tablelogs = 'masterfile_log';
    private $othersClass;
    private $hrislookup;
    public $style = 'width:100%;';
    private $fields = [];
    public $showclosebtn = true;
    private $logger;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->hrislookup = new hrislookup;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createTab($config)
    {
        $tab = [$this->gridname => ['gridcolumns' => ['action', 'clientname']]];

        $stockbuttons = ['delete'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][0]['style'] = "width:40px;whiteSpace: normal;max-width:40px;";
        $obj[0][$this->gridname]['columns'][1]['style'] = "width:800px;whiteSpace: normal;max-width:800px;";
        $obj[0][$this->gridname]['columns'][1]['readonly'] = true;

        $obj[0][$this->gridname]['columns'][1]['label'] = "Name";
        $obj[0][$this->gridname]['columns'][1]['type'] = "label";

        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['additem'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[0]['action'] = "lookupsetup";
        $obj[0]['action2'] = "lookupsetup";
        $obj[0]['lookupclass'] = "addapplicant";
        $obj[0]['icon'] = "person_add";
        $obj[0]['label'] = "ADD APPLICANT";
        return $obj;
    }

    public function lookupsetup($config)
    {
        $lookupclass = $config['params']['lookupclass2'];
        switch ($lookupclass) {
            case 'addapplicant':
                return $this->lookupledgerapplicant($config);
                break;
        }
    }

    public function lookupledgerapplicant($config)
    {
        $trno = $config['params']['tableid'];

        $lookupsetup = array(
            'type' => 'singlesearch',
            'title' => 'List of Applicants',
            'style' => 'width:900px;max-width:900px;'
        );

        $plotsetup = array(
            'plottype' => 'callback',
            'action' => 'addtogrid'
        );
        // lookup columns
        $cols = array();
        array_push($cols, array('name' => 'empcode', 'label' => 'Code', 'align' => 'left', 'field' => 'empcode', 'sortable' => true, 'style' => 'font-size:16px;'));
        array_push($cols, array('name' => 'empname', 'label' => 'Name', 'align' => 'left', 'field' => 'empname', 'sortable' => true, 'style' => 'font-size:16px;'));
        array_push($cols, array('name' => 'address', 'label' => 'Address', 'align' => 'left', 'field' => 'address', 'sortable' => true, 'style' => 'font-size:16px;'));

        $qry = "select " . $trno . " as trno, empid, empid as clientid, empcode,concat(emplast,', ',empfirst,' ',empmiddle) as empname,address,empmiddle,emplast,empfirst from app"; //where empcode=''
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }

    public function lookupcallback($config)
    {
        $row = $config['params']['row'];
        $data = $this->save('insert', $config);

        if ($data['status']) {
            return ['status' => true, 'msg' => $data['msg'], 'data' => $data['data'][0]];
        } else {
            return ['status' => false, 'msg' => $data['msg']];
        }
    } // end function

    public function save($action, $config)
    {
        $data = [];
        $row = $config['params']['row'];
        $trno = $config['params']['tableid'];

        $data = ['qid' => $trno, 'appid' => $row['empid']];
        foreach ($data as $key => $value) {
            $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        }

        $existclient = $this->coreFunctions->getfieldvalue("examinees", "appid", "qid=? and appid=?", [$trno, $data['appid']]);

        if ($existclient == 0) {
            if ($action == 'insert') {

                $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
                $data['createby'] = $config['params']['user'];

                if ($this->coreFunctions->sbcinsert($this->table, $data)) {
                    $config['params']['row']['trno'] = $trno;
                    $data =  $this->loaddataperrecord($config, $data['appid']);
                    $this->logger->sbcmasterlog($trno, $config, 'ADD APPLICANT ' . $row['empname']);
                    return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $data];
                } else {
                    return ['status' => false, 'msg' => 'Saving failed.', 'data' => []];
                }
            }
        } else {
            return ['status' => false, 'msg' => 'There was already an applicant named ' . $row['empname'] . '.', 'data' => []];
        }
    } //end function

    private function loaddataperrecord($tableid, $appid)
    {
        $select = $this->selectqry();
        $qry = $select . " where ex.qid=? and ex.appid=?";
        $data = $this->coreFunctions->opentable($qry, [$tableid, $appid]);
        return $data;
    }

    private  function selectqry()
    {
        return "select ex.qid, ex.appid, concat(app.emplast,', ',app.empfirst,' ',app.empmiddle) as clientname from examinees as ex left join app on app.empid=ex.appid";
    }

    public function loaddata($config)
    {
        $tableid = $config['params']['tableid'];
        $select = $this->selectqry();
        $qry = $select . " where ex.qid=?";
        $data = $this->coreFunctions->opentable($qry, [$tableid]);
        return $data;
    }
}
