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

class entryskillreq
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'SKILL REQUIREMENTS';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'jobtskills';
    public $tablelogs = 'masterfile_log';
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['line', 'skills'];
    public $showclosebtn = true;
    private $logger;
    private $hrislookup;


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
        $getcols = ['action', 'description'];

        foreach ($getcols as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $getcols]];

        $stockbuttons = ['save', 'delete'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:40px;whiteSpace: normal;min-width:40px;"; //action
        $obj[0][$this->gridname]['columns'][$description]['style'] = "width:900px;whiteSpace: normal;min-width:900px;"; //action
        $obj[0][$this->gridname]['columns'][$description]['readonly'] = true; //description

        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['addskillreq', 'deleteallitem'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[1]['label'] = 'Delete all';
        $obj[1]['lookupclass'] = 'loaddata';
        return $obj;
    }

    public function lookupsetup($config)
    {
        $lookupclass = $config['params']['lookupclass2'];
        switch ($lookupclass) {
            case 'addskillreq':
                return $this->hrislookup->lookupskillreq($config);
                break;
        }
    }

    public function lookupcallback($config)
    {
        $id = $config['params']['tableid'];
        $row = $config['params']['rows'];
        $data = [];
        foreach ($row  as $key2 => $value) {
            $config['params']['data']['line'] = 0;
            $config['params']['data']['trno'] = $id;
            $config['params']['data']['skills'] = $value['line'];
            $config['params']['data']['description'] = $value['itemname'];
            $config['params']['data']['bgcolor'] = 'bg-blue-2';
            $return = $this->save('insert', $config);
            if ($return['status']) {
                array_push($data, $return['row'][0]);
            }
        }

        return ['status' => true, 'msg' => 'Successfully added.', 'data' => $data];
    } // end function

    public function save($action, $config)
    {
        $data = [];
        $row = $config['params']['data'];
        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }
        $data['trno'] = $config['params']['tableid'];
        if ($row['line'] == 0) {
            $qry = "select line as value from " . $this->table . " where trno=? order by line desc limit 1";
            $line = $this->coreFunctions->datareader($qry, [$data['trno']]);
            if (!$line) {
                $line = 0;
            }
            $line = $line + 1;
            $data["line"] = $line;
            if ($this->coreFunctions->sbcinsert($this->table,  $data)) {

                $returnrow = $this->loaddataperrecord($data['trno'], $line, $config);

                $description = $this->coreFunctions->getfieldvalue('skillrequire', 'skill', 'line=?', [$data['skills']]);

                $this->logger->sbcmasterlog(
                    $data['trno'],
                    $config,
                    'CREATE SKILL REQ' . ' - DESCRIPTION: ' . $description . ' - LINE' . $line
                );
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        }
    } //end function

    public function delete($config)
    {
        $row = $config['params']['row'];
        $qry = "delete from jobtskills where trno=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line']]);

        $description = $this->coreFunctions->getfieldvalue('skillrequire', 'skill', 'line=?', [$row['skills']]);

        $this->logger->sbcmasterlog(
            $row['trno'],
            $config,
            'DELETE SKILL REQ' . ' - DESCRIPTION: ' . $description . ' - LINE' . $row['line']
        );

        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }

    public function deleteallitem($config)
    {
        $trno = $config['params']['tableid'];
        $qry = "delete from jobtskills where trno=?";
        $this->coreFunctions->execqry($qry, 'delete', [$trno]);
        return ['status' => true, 'msg' => 'Successfully deleted.', 'data' => []];
    }

    private function loaddataperrecord($clientid, $line)
    {
        $select = $this->selectqry();
        $qry = $select . " where js.trno=? and js.line=?";
        $data = $this->coreFunctions->opentable($qry, [$clientid, $line]);
        return $data;
    }

    private  function selectqry()
    {
        return "select js.line, js.skills, js.trno, sr.skill as description
      from jobtskills as js  
      left join skillrequire as sr on sr.line=js.skills";
    }

    public function loaddata($config)
    {
        $tableid = $config['params']['tableid'];
        $select = $this->selectqry();
        $qry = $select . " where js.trno=?";
        $data = $this->coreFunctions->opentable($qry, [$tableid]);
        return $data;
    }
} //end class
