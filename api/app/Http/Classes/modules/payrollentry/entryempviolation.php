<?php

namespace App\Http\Classes\modules\payrollentry;

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
use App\Http\Classes\reportheader;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class entryempviolation
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Employee Violation';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'clviolation';
    private $othersClass;
    public $style = 'width:100%;';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    // real clviolation columns only - violation/offense/vaction/type/divname are joined in, not stored here
    private $fields = ['clientid', 'vioid', 'dateid', 'divid', 'rem'];
    public $showclosebtn = false;
    private $reporter;
    public $logger;
    private $reportheader;


    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->reporter = new SBCPDF;
        $this->logger = new Logger;
        $this->reportheader = new reportheader;
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

        $columns = [
            'action',
            'violation',
            'offense',
            'vaction',
            'type',
            'divcode',
            'divname'
        ];

        foreach ($columns as $key => $value) {
            $$value = $key; //declare
        }

        $stockbuttons = ['save', 'delete'];
        $tab = [
            $this->gridname => [
                'gridcolumns' => $columns
            ]
        ];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:50px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$action]['label'] = 'Select';

        $obj[0][$this->gridname]['columns'][$violation]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
        $obj[0][$this->gridname]['columns'][$violation]['readonly'] = true;

        $obj[0][$this->gridname]['columns'][$offense]['style'] = "width:50px;whiteSpace: normal;min-width50px;";
        $obj[0][$this->gridname]['columns'][$offense]['readonly'] = true;

        $obj[0][$this->gridname]['columns'][$vaction]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$vaction]['readonly'] = true;

        $obj[0][$this->gridname]['columns'][$type]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$type]['label'] = 'Type';
        $obj[0][$this->gridname]['columns'][$type]['readonly'] = true;

        $obj[0][$this->gridname]['columns'][$divcode]['label'] = 'Code';
        $obj[0][$this->gridname]['columns'][$divcode]['type'] = 'lookup';
        $obj[0][$this->gridname]['columns'][$divcode]['lookupclass'] = 'lookupdetachment';
        $obj[0][$this->gridname]['columns'][$divcode]['action'] = 'lookupsetup';
        $obj[0][$this->gridname]['columns'][$divcode]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";

        $obj[0][$this->gridname]['columns'][$divname]['label'] = 'Detachment';
        $obj[0][$this->gridname]['columns'][$divname]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$divname]['style'] = "width:190px;whiteSpace: normal;min-width:190px;";


        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['addrecord', 'saveallentry', 'whlog']; // tab button
        $obj = $this->tabClass->createtabbutton($tbuttons);

        // 'addrecord' placeholder at index 0 gets repurposed into the lookup-triggered Add Violation button
        $obj[0]['icon'] = 'table_chart';
        $obj[0]['label'] = 'Add Violation';
        $obj[0]['lookupclass'] = 'lookupsgviolation';
        $obj[0]['action'] = 'lookupsetup';

        return $obj;
    }

    public function add($config)
    {
        $data = [];
        $data['line'] = 0;
        $data['clientid'] = $config['params']['tableid'];
        $data['vioid'] = 0;
        $data['violation'] = '';
        $data['offense'] = '';
        $data['vaction'] = '';
        $data['type'] = '';
        $data['divcode'] = '';
        $data['divid'] = '';
        $data['divname'] = '';
        $data['dateid'] = $this->othersClass->getCurrentDate();
        $data['rem'] = '';
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    public function selectqry()
    {
        $qry = "cv.line, cv.clientid, cv.vioid, sv.violation, sv.offense, sv.vaction, sv.type,
        cv.divid, dv.divcode, dv.divname, date(cv.dateid) as dateid, cv.rem";
        return $qry;
    }

    public function loaddata($config)
    {
        $clientid = $config['params']['tableid'];
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";

        $qry = "select " . $select . " from " . $this->table . " as cv
        left join sgviolation as sv on sv.line = cv.vioid
        left join division as dv on dv.divid = cv.divid
        where cv.clientid = ?
        order by cv.line";
        $data = $this->coreFunctions->opentable($qry, [$clientid]);
        return $data;
    }

    private function loaddataperrecord($line, $config)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";

        $qry = "select " . $select . " from " . $this->table . " as cv
        left join sgviolation as sv on sv.line = cv.vioid
        left join division as dv on dv.divid = cv.divid
        where cv.line = ?";
        $data = $this->coreFunctions->opentable($qry, [$line]);
        return $data;
    }

    public function save($config)
    {
        $data = [];
        $row = $config['params']['row'];
        $companyid = $config['params']['companyid'];

        $dateTables = ['clviolation'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

        foreach ($this->fields as $key => $field) {
            $value = isset($row[$field]) ? $row[$field] : '';
            $data[$field] = $this->othersClass->sanitizekeyfieldFast($field, $value, $lookups);
        }

        if (empty($data['vioid']) || $data['vioid'] == 0) {
            return ['status' => false, 'msg' => 'Saving failed. Please select a violation.'];
        }

        $lineValue = isset($row['line']) ? $row['line'] : 0;

        if ($lineValue == 0) {
            $data['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
            $data['encodedby'] = $config['params']['user'];
            $data['editby'] = $config['params']['user']; // NOT NULL column, must be set on insert
            $id = $this->coreFunctions->insertGetId($this->table, $data);
            if ($id != 0) {
                $returnrow = $this->loaddataperrecord($id, $config);
                $this->logger->sbcmasterlog(
                    $data['clientid'],
                    $config,
                    ' CREATE - Violation : ' . $row['violation'] . ' - ' . 'Offense : ' . $row['offense'] . ' - ' . 'Action : ' . $row['vaction'] . ' - ' . 'Type : ' . $row['type'] . ' - ' . 'Code : ' . $row['divcode'] . ' - ' . 'Detachment : ' . $row['divname'], 0, 1
                );
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];

            if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $lineValue]) == 1) {
                $returnrow = $this->loaddataperrecord($lineValue, $config);
                $this->logger->sbcmasterlog(
                    $data['clientid'],
                    $config,
                    ' UPDATE - Violation : ' . $row['violation'] . ' - ' . 'Offense : ' . $row['offense'] . ' - ' . 'Action : ' . $row['vaction'] . ' - ' . 'Type : ' . $row['type'] . ' - ' . 'Code : ' . $row['divcode'] . ' - ' . 'Detachment : ' . $row['divname'], 1, 1 
                );
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        }
    } //end function

    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        $companyid = $config['params']['companyid'];

        $dateTables = ['clviolation'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

        foreach ($data as $key => $rowvalue) {
            $data2 = [];
            if (!empty($data[$key]['bgcolor'])) {
                foreach ($this->fields as $key2 => $field) {
                    $value = isset($data[$key][$field]) ? $data[$key][$field] : '';
                    $data2[$field] = $this->othersClass->sanitizekeyfieldFast($field, $value, $lookups);
                }

                if (empty($data2['vioid']) || $data2['vioid'] == 0) {
                    return ['status' => false, 'msg' => 'Saving failed. Please select a violation for all rows.'];
                }

                $lineValue = isset($data[$key]['line']) ? $data[$key]['line'] : 0;

                if ($lineValue == 0) {
                    $data2['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['encodedby'] = $config['params']['user'];
                    $data2['editby'] = $config['params']['user']; // NOT NULL column, must be set on insert
                    $id = $this->coreFunctions->insertGetId($this->table, $data2);
                    $this->logger->sbcmasterlog(
                        $data2['clientid'],
                        $config,
                        ' CREATE - Violation : ' . $data[$key]['violation'] . ' - ' . 'Offense : ' . $data[$key]['offense'] . ' - ' . 'Action : ' . $data[$key]['vaction'] . ' - ' . 'Type : ' . $data[$key]['type'] . ' - ' . 'Code : ' . $data[$key]['divcode'] . ' - ' . 'Detachment : ' . $data[$key]['divname'], 0, 1
                    );
                } else {
                    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['editby'] = $config['params']['user'];
                    $this->coreFunctions->sbcupdate(
                        $this->table,
                        $data2,
                        ['line' => $lineValue]
                    );
                    $this->logger->sbcmasterlog(
                        $data2['clientid'],
                        $config,
                        ' UPDATE - Violation : ' . $data[$key]['violation'] . ' - ' . 'Offense : ' . $data[$key]['offense'] . ' - ' . 'Action : ' . $data[$key]['vaction'] . ' - ' . 'Type : ' . $data[$key]['type'] . ' - ' . 'Code : ' . $data[$key]['divcode'] . ' - ' . 'Detachment : ' . $data[$key]['divname'], 1, 1
                    );
                }
            }
        }
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved Successfully.', 'data' => $returndata];
    }

    public function delete($config)
    {
        $row = $config['params']['row'];
        $qry = "delete from " . $this->table . " where line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
        $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE - Violation : ' . $row['violation'] . ' - ' . 'Offense : ' . $row['offense'] . ' - ' . 'Action : ' . $row['vaction'] . ' - ' . 'Type : ' . $row['type'] . ' - ' . 'Code : ' . $row['divcode'] . ' - ' . 'Detachment : ' . $row['divname'], 1, 1);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }

    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'whlog':
                return $this->lookuplogs($config);
                break;
            case 'lookupsgviolation':
                return $this->lookupsgviolation($config);
                break;
            case 'lookupdetachment':
                return $this->lookupdetachment($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
                break;
        }
    }

    public function lookupsgviolation($config)
    {
        $lookupsetup = array(
            'type' => 'multi',
            'rowkey' => 'line',
            'title' => 'Violation Types',
            'style' => 'width:900px;max-width:900px;'
        );

        $plotsetup = array(
            'plottype' => 'tableentry',
            'action' => 'addtogrid',
        );

        $cols = array(
            array('name' => 'violation', 'label' => 'Violation', 'align' => 'left', 'field' => 'violation', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'offense', 'label' => 'Offense', 'align' => 'left', 'field' => 'offense', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'vaction', 'label' => 'Action', 'align' => 'left', 'field' => 'vaction', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'type', 'label' => 'Type', 'align' => 'left', 'field' => 'type', 'sortable' => true, 'style' => 'font-size:16px;')
        );

        $query = "select line, violation, offense, vaction, type from sgviolation";

        $data = $this->coreFunctions->opentable($query);

        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }

    public function lookupdetachment($config)
    {
        $plotting = array('divcode' => 'divcode', 'divid' => 'divid', 'divname' => 'divname');
        $plottype = 'plotgrid';
        $lookupsetup = array(
            'type' => 'single',
            'title' => 'Bill Type Lookup',
            'style' => 'width:400px;max-width:400px;'
        );

        $plotsetup = array(
            'plottype' => $plottype,
            'action' => '',
            'plotting' => $plotting
        );

        $cols = array(
            array('name' => 'code', 'label' => 'Code', 'align' => 'left', 'field' => 'divcode', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'detachment', 'label' => 'Detachment', 'align' => 'left', 'field' => 'divname', 'sortable' => true, 'style' => 'font-size:16px;')
        );

        $query = "select divcode, divid, divname from division where divcode != '' order by divcode";

        $data = $this->coreFunctions->opentable($query);

        $index = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
    }


    public function lookupcallback($config)
    {
        $clientid = $config['params']['tableid'];
        $row = $config['params']['rows'];
        $returndata = [];
        $status = true;
        $msg = 'Successfully added.';

        foreach ($row as $key2 => $value) {
            $config['params']['row']['line'] = 0;
            $config['params']['row']['clientid'] = $clientid;
            $config['params']['row']['vioid'] = $row[$key2]['line'];
            $config['params']['row']['violation'] = $row[$key2]['violation'];
            $config['params']['row']['offense'] = $row[$key2]['offense'];
            $config['params']['row']['vaction'] = $row[$key2]['vaction'];
            $config['params']['row']['type'] = $row[$key2]['type'];
            $config['params']['row']['divid'] = '';
            $config['params']['row']['divcode'] = '';
            $config['params']['row']['divname'] = '';
            $config['params']['row']['dateid'] = $this->othersClass->getCurrentDate();
            $config['params']['row']['rem'] = '';
            $config['params']['row']['bgcolor'] = '';

            $return = $this->save($config);
            if ($return['status']) {
                array_push($returndata, $return['row'][0]);
            } else {
                $status = false;
                $msg = $return['msg'];
            }
        }

        return ['status' => $status, 'msg' => $msg, 'data' => $returndata];
    }

    public function lookuplogs($config)
    {
        $doc = 'ENTRYEMPVIOLATION';
        $lookupsetup = array(
            'type' => 'show',
            'title' => 'Logs',
            'style' => 'width:1000px;max-width:1000px;'
        );

        // lookup columns
        $cols = array(
            array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')
        );

        $qry = "
        select trno, doc, task, log.user, dateid, 
        if(pic='','blank_user.png',pic) as pic
        from " . $this->tablelogs . " as log
        left join useraccess as u on u.username=log.user
        where log.doc = '" . $doc . "'
        union all
        select trno, doc, task, log.user, dateid, 
        if(pic='','blank_user.png',pic) as pic
        from  " . $this->tablelogs_del . " as log
        left join useraccess as u on u.username=log.user
        where log.doc = '" . $doc . "'";

        $qry = $qry . " order by dateid desc";
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
    }
}//end class