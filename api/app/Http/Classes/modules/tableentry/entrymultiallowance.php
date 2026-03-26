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
use App\Http\Classes\SBCPDF;

class entrymultiallowance
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'ALLOWANCE';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $logger;
    private $table = 'allowsetuptemp';
    private $othersClass;
    public $style = 'width:100%;';
    public $tablelogs = 'hrisnum_log';
    public $tablelogs_del = 'del_hrisnum_log';
    private $fields = ['dateid', 'dateeffect', 'dateend', 'empid', 'allowance', 'acnoid', 'remarks', 'refx', 'isliquidation'];
    public $showclosebtn = false;
    private $reporter;


    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->reporter = new SBCPDF;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 0
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $tableid = $config['params']['tableid'];

        $columns = ['action', 'code', 'codename', 'dateeffect', 'dateend', 'allowance', 'isliquidation', 'remarks'];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $columns]];

        $stockbuttons = ['delete'];

        $isposted = $this->othersClass->isposted2($tableid, "hrisnum");
        if ($isposted) {
            $stockbuttons = [];
        }

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:100px;whiteSpace: normal;min-width:120px;";
        $obj[0][$this->gridname]['columns'][$code]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$codename]['style'] = "width:400px;whiteSpace: normal;min-width:400px;";
        $obj[0][$this->gridname]['columns'][$dateeffect]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$dateend]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$allowance]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$remarks]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";

        $obj[0][$this->gridname]['columns'][$code]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$codename]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$dateeffect]['readonly'] = false;
        $obj[0][$this->gridname]['columns'][$dateend]['readonly'] = false;
        $obj[0][$this->gridname]['columns'][$code]['label'] = 'Allowance Code';
        $obj[0][$this->gridname]['columns'][$codename]['label'] = 'Allowance Type';
        $obj[0][$this->gridname]['columns'][$dateeffect]['label'] = 'Period Start';
        $obj[0][$this->gridname]['columns'][$dateend]['label'] = 'Period End';
        $obj[0][$this->gridname]['columns'][$allowance]['label'] = 'Amount';

        if ($isposted) {
            $obj[0][$this->gridname]['columns'][$action]['type'] = 'coldel';
        }

        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }


    public function createtabbutton($config)
    {
        $tbuttons = ['additem', 'saveallentry'];
        $trno = $config['params']['tableid'];
        $isposted = $this->othersClass->isposted2($trno, "hrisnum");
        $obj = $this->tabClass->createtabbutton($tbuttons);

        $obj[0]['action'] = "lookupsetup";
        $obj[0]['action2'] = "lookupsetup";
        $obj[0]['label'] = "ADD";

        if ($isposted) {
            $obj[0]['visible'] = false;
            $obj[1]['visible'] = false;
        }

        return $obj;
    }


    public function add($config)
    {
        $data = [];
        $data['trno'] = 0;
        $data['code'] = '';
        $data['codename'] = '';
        $data['dateid'] = '';
        $data['dateeffect'] = ''; //date('Y-m-d')
        $data['dateend'] = ''; //date('Y-m-d')
        $data['allowance'] = 0;
        $data['empid'] = $config['params']['tableid'];
        $data['remarks'] = '';
        $data['refx'] = 0;
        $data['isliquidation'] = 'false';
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    private function selectqry()
    {
        $qry = "a.trno,date(a.dateeffect) as dateeffect, date(a.dateend) as dateend,date(a.dateid) as dateid,
                a.empid,a.remarks,a.allowance,a.acnoid,a.refx,(case when a.isliquidation = 0 then 'false' else 'true' end) as isliquidation";

        return $qry;
    }

    public function delete($config)
    {
        $tableid = $config['params']['tableid'];

        $isposted = $this->othersClass->isposted2($tableid, "hrisnum");
        if ($isposted) {
            return ['status' => false, 'msg' => 'Cannot delete entry, alreay posted.'];
        }

        // $row = $config['params']['row'];
        // $data = $this->loaddataperrecord($tableid, $row['acnoid']);

        // $codename = $this->coreFunctions->getfieldvalue("paccount", "codename", "line=?", [$row['acnoid']]);

        // $qry = "delete from " . $this->table . " where acnoid=? and refx=?";
        // $this->coreFunctions->execqry($qry, 'delete', [$row['acnoid'], $tableid]);

        // $this->logger->sbcwritelog($tableid, $config, 'DELETE', 'REMOVE: ' . $codename);
        // return ['status' => true, 'msg' => 'Successfully deleted.'];
    }


    private function loaddataperrecord($trno, $acnoid)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor,p.codename,p.code ";
        $qry = "select " . $select . " 
                from " . $this->table . " as a
                left join paccount as p on p.line=a.acnoid where a.acnoid=? and a.refx=?";
        $data = $this->coreFunctions->opentable($qry, [$acnoid, $trno]);
        return $data;
    }

    public function loaddata($config)
    {
        $tableid = $config['params']['tableid'];
        $filter = '';
        // if ($config['params']['doc'] == 'HS') {
        //     $empid = $this->coreFunctions->getfieldvalue("eschange", "empid", "trno=?", [$tableid], '', true);
        //     if ($empid != 0) {
        //         $filter = " or a.empid= $empid ";
        //     }
        // }
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor,p.codename,p.code ";
        $qry = "select " . $select . " from " . $this->table . " as a
                left join paccount as p on p.line=a.acnoid
                where a.refx = " . $tableid . " $filter order by a.dateeffect,codename";
        // $this->coreFunctions->logConsole($qry);
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }


    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'additem':
                return $this->lookupaddallowance($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
                break;
        }
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
    }

    public function lookupaddallowance($config)
    {
        $trno = $config['params']['tableid'];

        $table = '';
        switch ($config['params']['doc']) {
            case 'HS': //employment status change
                $table = "eschange";
                break;
            case 'HJ': //job offer
                $table = "joboffer";
                break;
            case 'RATESETUP':
                $table = "ratesetup";
                break;
        }

        $effdate = $this->coreFunctions->getfieldvalue($table, 'date(effdate)', 'trno=?', [$trno]);

        $lookupsetup = array(
            'type' => 'single',
            'title' => 'List of Accounts',
            'style' => 'width:900px;max-width:900px;'
        );

        $plotsetup = array(
            'plottype' => 'callback',
            'action' => 'addtogrid'
        );
        // lookup columns
        $cols = [];
        array_push($cols, array('name' => 'code', 'label' => 'Account Code', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'));
        array_push($cols, array('name' => 'codename', 'label' => 'Account Name', 'align' => 'left', 'field' => 'codename', 'sortable' => true, 'style' => 'font-size:16px;'));

        $qry = "select " . $trno . " as trno,line,code, codename, '$effdate' as dateeffect, '' as dateend, 
                       0 as allowance, '' as remarks,0 as isliquidation
                from paccount 
                where alias = 'ALLOWANCE' and line not in (select acnoid from allowsetup where refx = $trno)";
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    } //end function

    public function save($action, $config)
    {
        $data = [];
        $row = $config['params']['row'];
        $trno = $config['params']['tableid'];

        switch ($config['params']['doc']) {
            case 'HS': //employment status change
                $empid = $this->coreFunctions->getfieldvalue("eschange", 'empid', 'trno=?', [$trno]);
                $type = $this->coreFunctions->getfieldvalue("employee", 'classrate', 'empid=?', [$empid]);

                break;
            case 'HJ': //job offer
                $empid = 0;
                $type = $this->coreFunctions->getfieldvalue("joboffer", 'classrate', 'empid=?', [$empid]);
                switch ($type) {
                    case 'Monthly':
                        $type = 'M';
                        break;
                    case 'Daily':
                        $type = 'D';
                        break;
                }

                if ($row['dateeffect'] == NULL) {
                    $row['dateeffect'] = $this->othersClass->getCurrentTimeStamp();
                }
                break;
            case 'RATESETUP':
                $empid = $trno;
                $type = $this->coreFunctions->getfieldvalue("employee", 'classrate', 'empid=?', [$empid]);
                $row['dateeffect'] = $this->othersClass->getCurrentTimeStamp();
                break;
        }

        $data = [
            'refx' => $trno,
            'acnoid' => $row['line'],
            'dateeffect' => $row['dateeffect'],
            'dateend' => '9999-12-31',
            'empid' => $empid,
            'dateid' =>  $this->othersClass->getCurrentTimeStamp(),
            'type' => $type,
            'remarks' => $row['remarks'],
            'allowance' => $row['allowance']
        ];

        $codename = $this->coreFunctions->getfieldvalue("paccount", "codename", "line=?", [$data['acnoid']]);

        foreach ($data as $key => $value) {
            $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        }

        switch ($config['params']['doc']) {
            case 'RATESETUP':
                $existallow = $this->coreFunctions->getfieldvalue("allowsetup", "acnoid", "acnoid=? and empid=?", [$data['acnoid'], $empid]);
                break;
            case 'HJ': //job offer
                $existallow = $this->coreFunctions->getfieldvalue("allowsetup", "acnoid", "acnoid=? and refx=?", [$data['acnoid'], $trno]);
                break;
            case 'HS':
                if ($config['params']['companyid'] == 58) { //cdo
                    $existallow = $this->coreFunctions->getfieldvalue("allowsetuptemp", "acnoid", "acnoid=? and empid=? and refx=?", [$data['acnoid'], $empid, $data['refx']]);
                }
                break;
        }
        if ($existallow == 0) {
            if ($action == 'insert') {
                $qry = "select trno as value from " . $this->table . " order by trno desc limit 1";
                $line = $this->coreFunctions->datareader($qry);
                if ($line == '') {
                    $line = 0;
                }
                $line = $line + 1;
                $data['trno'] = $line;
                if ($this->coreFunctions->sbcinsert($this->table, $data)) {
                    $data =  $this->loaddataperrecord($trno, $data['acnoid']);
                    // $this->logger->sbcwritelog($trno, $config, 'CREATE', 'ADD: ' . $codename);
                    return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $data];
                } else {
                    return ['status' => false, 'msg' => 'Saving failed.', 'data' => []];
                }
            }
        } else {
            return ['status' => false, 'msg' => $codename . ' has already been added.', 'data' => []];
        }
    } //end function

    public function saveallentry($config)
    {
        $trno = $config['params']['tableid'];
        $this->coreFunctions->sbcupdate("cntnum", ['statid' => 0], ['trno' => $trno]);

        $empid = $this->coreFunctions->getfieldvalue("eschange", 'empid', 'trno=?', [$trno]);
        $type = $this->coreFunctions->getfieldvalue("employee", 'classrate', 'empid=?', [$empid]);
        $data = $config['params']['data'];

        $data['type'] = $type;
        foreach ($data as $key => $value) {
            $data2 = [];

            if (!empty($data[$key]['bgcolor'])) {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }

                if ($data[$key]['trno'] != 0) {
                    $codename = $this->coreFunctions->getfieldvalue("paccount", "codename", "line=?", [$data[$key]['acnoid']]);

                    $this->coreFunctions->sbcupdate($this->table, $data2, ['trno' => $data[$key]['trno'], 'refx' => $data[$key]['refx']]);
                    $this->logger->sbcwritelog($trno, $config, 'SETUP ALLOWANCE', $codename . ' : ' . $data[$key]['allowance']);
                }
            }
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata, 'reloadhead' => true];
    } // end function $$

} //end class
