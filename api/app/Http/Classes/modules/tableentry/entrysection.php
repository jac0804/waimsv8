<?php

namespace App\Http\Classes\modules\tableentry;

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
use App\Http\Classes\lookup\enrollmentlookup;

class entrysection
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'SECTION';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'section';
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['sectcode', 'sectname'];
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    public $showclosebtn = true;
    private $enrollmentlookup;
    private $logger;

    public function __construct($params = null)
    {
        $this->fieldClass = new txtfieldClass($params);
        $this->tabClass = new tabClass($params);;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->enrollmentlookup = new enrollmentlookup;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createTab($config)
    {

        $getcols = ['action', 'description', 'sectcode', 'sectname', 'code'];

        foreach ($getcols as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $getcols]];
        $stockbuttons = ['save', 'delete'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$description]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";
        $obj[0][$this->gridname]['columns'][$description]['label'] = "";
        $obj[0][$this->gridname]['columns'][$description]['type'] = "hidden";

        $obj[0][$this->gridname]['columns'][$sectcode]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$sectname]['style'] = "width:250px;whiteSpace: normal;min-width:250px;";

        $obj[0][$this->gridname]['columns'][$code]['label'] = "";
        $obj[0][$this->gridname]['columns'][$code]['type'] = "hidden";
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['addrecord', 'saveallentry', 'deleteallitem', 'whlog'];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        $obj[2]['label'] = 'Delete all';
        $obj[2]['lookupclass'] = 'loaddata';

        if ($config['params']['tableid'] == 0) {
            foreach ($obj as $key => $value) {
                $obj[$key]['visible'] = false;
            }
        }
        return $obj;
    }

    public function loaddata($config)
    {
        $deptid = $config['params']['tableid'];
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " where deptid=? 
        order by sectid";

        $this->coreFunctions->LogConsole($deptid);
        $this->coreFunctions->LogConsole($qry);

        $data = $this->coreFunctions->opentable($qry, [$deptid]);
        return $data;
    }

    private function selectqry()
    {
        $qry = "sectid, deptid";
        foreach ($this->fields as $key => $value) {
            $qry = $qry . ',' . $value;
        }
        return $qry;
    }

    public function add($config)
    {
        $data = [];
        $data['deptid'] = $config['params']['tableid'];
        $data['sectid'] = 0;
        $data['sectcode'] = '';
        $data['sectname'] = '';
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    public function save($config)
    {
        if ($config['params']['tableid'] == 0) {
            return ['status' => false, 'msg' => 'Please save the department first.'];
        }

        $data = [];
        $row = $config['params']['row'];
        $companyid = $config['params']['companyid'];
        $dateTables = ['section'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfieldFast($value, $row[$value], $lookups);
        }
        $data['deptid'] = $config['params']['tableid'];

        if (trim($data['sectcode']) == '' || trim($data['sectname']) == '') {
            return ['status' => false, 'msg' => 'Section code and name are required.'];
        }

        $dup = $this->coreFunctions->datareader(
            "select sectid as value from " . $this->table . " where sectcode=? and sectid<>? limit 1",
            [$data['sectcode'], $row['sectid']]
        );
        if ($dup != '') {
            return ['status' => false, 'msg' => 'Section code ' . $data['sectcode'] . ' already exists.'];
        }

        $dup = $this->coreFunctions->datareader(
            "select sectid as value from " . $this->table . " where deptid=? and sectname=? and sectid<>? limit 1",
            [$data['deptid'], $data['sectname'], $row['sectid']]
        );
        if ($dup != '') {
            return ['status' => false, 'msg' => 'Section name ' . $data['sectname'] . ' already exists in this department.'];
        }

        if ($row['sectid'] == 0) {
            $data['createby'] = $config['params']['user'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();

            $line = $this->coreFunctions->insertGetId($this->table, $data);

            if ($line) {
                $returnrow = $this->loaddataperrecord($data['deptid'], $line, $config);

                $this->logger->sbcmasterlog(
                    $data['deptid'],
                    $config,
                    'CREATE SECTION' . ' - CODE: ' . $data['sectcode'] . ' - NAME: ' . $data['sectname'] . ' - SECTID' . $line
                );
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {
            $data['editby'] = $config['params']['user'];
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();

            if ($this->coreFunctions->sbcupdate($this->table, $data, ['deptid' => $data['deptid'], 'sectid' => $row['sectid']]) == 1) {
                $returnrow = $this->loaddataperrecord($data['deptid'], $row['sectid'], $config);

                $this->logger->sbcmasterlog(
                    $data['deptid'],
                    $config,
                    'UPDATE SECTION' . ' - CODE: ' . $data['sectcode'] . ' - NAME: ' . $data['sectname'] . ' - SECTID' . $row['sectid'],
                    1
                );
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        }
    }

    public function delete($config)
    {
        $row = $config['params']['row'];
        $deptid = $config['params']['tableid'];

        $this->logger->sbcmasterlog(
            $deptid,
            $config,
            'DELETE SECTION' . ' - CODE: ' . $row['sectcode'] . ' - NAME: ' . $row['sectname'] . ' - SECTID' . $row['sectid']
        );

        $qry = "delete from " . $this->table . " where deptid=? and sectid=?";
        $this->coreFunctions->execqry($qry, 'delete', [$deptid, $row['sectid']]);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }

    public function deleteallitem($config)
    {
        $deptid = $config['params']['tableid'];
        $qry = "delete from " . $this->table . " where deptid=?";
        $this->coreFunctions->execqry($qry, 'delete', [$deptid]);
        return ['status' => true, 'msg' => 'Successfully deleted.', 'data' => []];
    }

    private function loaddataperrecord($deptid, $sectid, $config)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " where deptid=? and sectid=?";
        $data = $this->coreFunctions->opentable($qry, [$deptid, $sectid]);
        return $data;
    }

    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        $companyid = $config['params']['companyid'];

        if ($config['params']['tableid'] == 0) {
            return ['status' => false, 'msg' => 'Please save the department first.'];
        }

        $dateTables = ['section'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

        $deptid = $config['params']['tableid'];
        $codes = [];
        $names = [];
        foreach ($data as $key => $value) {
            if ($data[$key]['bgcolor'] != '') {
                $code = trim($data[$key]['sectcode']);
                $name = trim($data[$key]['sectname']);

                if ($code == '' || $name == '') {
                    return ['status' => false, 'msg' => 'Section code and name are required.'];
                }

                $dup = $this->coreFunctions->datareader(
                    "select sectid as value from " . $this->table . " where sectcode=? and sectid<>? limit 1",
                    [$code, $data[$key]['sectid']]
                );
                if ($dup != '' || in_array(strtolower($code), $codes)) {
                    return ['status' => false, 'msg' => 'Section code ' . $code . ' already exists.'];
                }

                $dup = $this->coreFunctions->datareader(
                    "select sectid as value from " . $this->table . " where deptid=? and sectname=? and sectid<>? limit 1",
                    [$deptid, $name, $data[$key]['sectid']]
                );
                if ($dup != '' || in_array(strtolower($name), $names)) {
                    return ['status' => false, 'msg' => 'Section name ' . $name . ' already exists in this department.'];
                }

                $codes[] = strtolower($code);
                $names[] = strtolower($name);
            }
        }

        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfieldFast($value2, $data[$key][$value2], $lookups);
                }
                $data2['deptid'] = $config['params']['tableid'];
                if ($data[$key]['sectid'] == 0) {
                    $data2['createby'] = $config['params']['user'];
                    $data2['createdate'] = $this->othersClass->getCurrentTimeStamp();

                    $line = $this->coreFunctions->insertGetId($this->table, $data2);

                    $this->logger->sbcmasterlog(
                        $config['params']['tableid'],
                        $config,
                        'INSERT SECTION' . ' - CODE: ' . $data[$key]['sectcode'] . ' - NAME: ' . $data[$key]['sectname'] . ' - SECTID' . $line
                    );
                } else {
                    $data2['editby'] = $config['params']['user'];
                    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['deptid' => $config['params']['tableid'], 'sectid' => $data[$key]['sectid']]);

                    $this->logger->sbcmasterlog(
                        $config['params']['tableid'],
                        $config,
                        'UPDATE SECTION' . ' - CODE: ' . $data[$key]['sectcode'] . ' - NAME: ' . $data[$key]['sectname'] . ' - SECTID' . $data[$key]['sectid'],
                        1
                    );
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'Saved all Successfully', 'data' => $returndata, 'row' => $returndata];
    }

    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'whlog':
                return $this->lookuplogs($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup...'];
                break;
        }
    }

    public function lookuplogs($config)
    {
        $doc = 'DEPARTMENT';
        $lookupsetup = array(
            'type' => 'show',
            'title' => 'Section Master Logs',
            'style' => 'width:100%;max-width:90%;height:50%;'
        );

        // lookup columns
        $cols = array(
            array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

        );

        $trno = $config['params']['tableid'];

        $qry = "
        select trno, doc, task, log.user, dateid, 
        if(pic='','blank_user.png',pic) as pic
        from " . $this->tablelogs . " as log
        left join useraccess as u on u.username=log.user
        where log.doc = '" . $doc . "' and log.trno = '" . $trno . "'
        union all
        select trno, doc, task, log.user, dateid, 
        if(pic='','blank_user.png',pic) as pic
        from  " . $this->tablelogs_del . " as log
        left join useraccess as u on u.username=log.user
        where log.doc = '" . $doc . "' and log.trno = '" . $trno . "'";

        $qry = $qry . " order by dateid desc";
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
    }
}
