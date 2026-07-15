<?php

namespace App\Http\Classes\modules\autoserventry;

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

class entryvehicle
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'CUSTOMER VEHICLE';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'cvehicle';
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['licenseno', 'mileage', 'cmake', 'carengine', 'transmission', 'motorno', 'chassis', 'mvno', 'insurance', 'labor'];
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    public $showclosebtn = true;
    private $enrollmentlookup;
    private $logger;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
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

        $getcols = ['action', 'description','cryear', 'licenseno', 'mileage', 'cmake', 'model', 'sub_model', 'crtype', 
                    'carengine', 'transmission', 'motorno', 'chassis', 'mvno', 'insurance', 'labor', 'code'];

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

        $obj[0][$this->gridname]['columns'][$model]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$model]['readonly'] = true;

        $obj[0][$this->gridname]['columns'][$cryear]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";

        $obj[0][$this->gridname]['columns'][$crtype]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";

        $obj[0][$this->gridname]['columns'][$sub_model]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$sub_model]['readonly'] = true;

        $obj[0][$this->gridname]['columns'][$chassis]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";

        $obj[0][$this->gridname]['columns'][$insurance]['align'] = "text-left";
        $obj[0][$this->gridname]['columns'][$insurance]['readonly'] = false;

        $obj[0][$this->gridname]['columns'][$code]['label'] = "";
        $obj[0][$this->gridname]['columns'][$code]['type'] = "hidden";
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['addvehicle', 'saveallentry', 'deleteallitem', 'whlog'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[0]['action']      = 'lookupsetup';
        $obj[0]['lookupclass'] = 'lookupvehicle';

        $obj[2]['label'] = 'Delete all';
        $obj[2]['lookupclass'] = 'loaddata';
        return $obj;
    }

    public function loaddata($config)
    {
        $carid = $config['params']['tableid'];
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";

        $qry = "select " . $select . " 
        from " . $this->table . " as cv
        left join cmodel as cm on cm.carid = cv.carid and cm.line = cv.cmodelline
        where cv.clientid = ? 
        order by cv.line";

        $this->coreFunctions->LogConsole($carid);
        $this->coreFunctions->LogConsole($qry);

        $data = $this->coreFunctions->opentable($qry, [$carid]);
        return $data;
    }

    private function selectqry()
    {
        $qry = "cv.line, cv.clientid, cv.carid, cv.cmodelline";

        foreach ($this->fields as $key => $value) {
            $qry = $qry . ', cv.' . $value;
        }

        $qry = $qry . ', cm.model, cm.cryear, cm.crtype, cm.sub_model';

        return $qry;
    }

    public function add($config)
    {
        $data = [];
        return $data;
    }

    public function save($config)
    {
        $data = [];
        $row = $config['params']['row'];
        $companyid = $config['params']['companyid'];
        $dateTables = ['cvehicle'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);
        foreach ($this->fields as $key => $value) {
            // $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
            $data[$value] = $this->othersClass->sanitizekeyfieldFast($value, $row[$value], $lookups);
        }

        // $data['carid']      = $this->othersClass->sanitizekeyfield('carid', $row['carid']);
        // $data['cmodelline'] = $this->othersClass->sanitizekeyfield('cmodelline', $row['cmodelline']);

        $data['carid'] = $this->othersClass->sanitizekeyfieldFast('carid', $row['carid'], $lookups);
        $data['cmodelline'] = $this->othersClass->sanitizekeyfieldFast('cmodelline', $row['cmodelline'], $lookups);
        $data['clientid'] = $config['params']['tableid'];

        if ($data['carid'] == 0 || $data['cmodelline'] == 0) {
            $data[0]['bgcolor'] = 'bg-red-2';
            $data[0]['line'] = $row['line'];
            return ['status' => false, 'msg' => 'Invalid model selection.'];
        }

        if ($row['line'] == 0) {

            $data['createby']   = $config['params']['user'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();

            $line = $this->coreFunctions->insertGetId($this->table, $data);

            if ($line) {
                $returnrow = $this->loaddataperrecord($data['clientid'], $line, $config);
                $this->logger->sbcmasterlog(
                    $data['clientid'],
                    $config,
                    'CREATE CAR MODEL' . ' - MODEL LINE: ' . $data['cmodelline'] . ' - LINE' . $line
                );
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {
            $data['editby']     = $config['params']['user'];
            $data['editdate']   = $this->othersClass->getCurrentTimeStamp();

            if ($this->coreFunctions->sbcupdate($this->table, $data, ['clientid' => $data['clientid'], 'line' => $row['line']]) == 1) {
                $returnrow = $this->loaddataperrecord($data['clientid'], $row['line'], $config);
                $this->logger->sbcmasterlog(
                    $data['clientid'],
                    $config,
                    'UPDATE CAR MODEL' . ' - MODEL LINE: ' . $data['cmodelline'] . ' - LINE' . $row['line'],
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
        $clientid = $config['params']['tableid'];

        $this->logger->sbcmasterlog(
            $clientid,
            $config,
            'DELETE CAR MODEL' . ' - MODELLINE: ' . $row['cmodelline'] . ' - LINE' . $row['line']
        );

        $qry = "delete from " . $this->table . " where clientid=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$clientid, $row['line']]);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }

    public function deleteallitem($config)
    {
        $clientid = $config['params']['tableid'];
        $qry = "delete from " . $this->table . " where clientid=?";
        $this->coreFunctions->execqry($qry, 'delete', [$clientid]);
        return ['status' => true, 'msg' => 'Successfully deleted.', 'data' => []];
    }

    private function loaddataperrecord($carid, $line, $config)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        // $qry = "select " . $select . " from " . $this->table . " where carid=? and line=?";
        $qry = "select " . $select . "
        from " . $this->table . " as cv
        left join cmodel as cm on cm.carid = cv.carid and cm.line = cv.cmodelline
        where cv.clientid = ? and cv.line = ?";
        $data = $this->coreFunctions->opentable($qry, [$carid, $line]);
        return $data;
    }

    public function saveallentry($config)
    {
        $clientid = $config['params']['tableid'];
        $data = $config['params']['data'];
        $dateTables = ['cvehicle'];
        $companyid = $config['params']['companyid'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);
        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    // $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                    $data2[$value2] = $this->othersClass->sanitizekeyfieldFast($value2, $data[$key][$value2], $lookups);
                }

                // $data2['carid']      = $this->othersClass->sanitizekeyfield('carid', $data[$key]['carid']);
                // $data2['cmodelline'] = $this->othersClass->sanitizekeyfield('cmodelline', $data[$key]['cmodelline']);
                $data2['carid'] = $this->othersClass->sanitizekeyfieldFast('carid', $data[$key]['carid'], $lookups);
                $data2['cmodelline'] = $this->othersClass->sanitizekeyfieldFast('cmodelline', $data[$key]['cmodelline'], $lookups);
                $data2['clientid']   = $clientid;

                if ($data[$key]['line'] == 0) {
                    $data2['createby']   = $config['params']['user'];
                    $data2['createdate'] = $this->othersClass->getCurrentTimeStamp();

                    $line = $this->coreFunctions->insertGetId($this->table, $data2);

                    $this->logger->sbcmasterlog($clientid, $config, 'INSERT CAR MODEL' . ' - MODELLINE: ' . $data[$key]['cmodelline'] . ' - LINE: ' . $data[$key]['line']);
                } else {
                    $data2['editby']     = $config['params']['user'];
                    $data2['editdate']   = $this->othersClass->getCurrentTimeStamp();
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['clientid' => $clientid, 'line' => $data[$key]['line']]);

                    $this->logger->sbcmasterlog($clientid, $config, 'UPDATE CAR MODEL' . ' - MODELLINE: ' . $data[$key]['cmodelline'] . ' - LINE' . $data[$key]['line']);
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
            case 'lookupvehicle':
                return $this->lookupvehicle($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup...'];
                break;
        }
    }

    public function lookupvehicle($config)
    {
        // $plotting = array('cmake' => 'cmake', 'model' => 'model', 'cryear' => 'cryear', 'crtype' => 'crtype', 'sub_model' => 'sub_model', 'carid' => 'carid', 'cmodelline' => 'cmodelline');
        $lookupsetup = array(
            'type' => 'multi',
            'rowkey' => 'cmodelline',
            'title' => 'List Of Vehicle',
            'style' => 'width:900px;max-width:900px;'
        );
        $plotsetup = array(
            'plottype' => 'tableentry',
            'action' => 'addtogrid',
            // 'plotting' => $plotting,
        );

        $cols = [
            ['name' => 'cmake', 'label' => 'Car Make', 'align' => 'left', 'field' => 'cmake', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'model', 'label' => 'Car Model', 'align' => 'left', 'field' => 'model', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'cryear', 'label' => 'Year', 'align' => 'left', 'field' => 'cryear', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'crtype', 'label' => 'Type', 'align' => 'left', 'field' => 'crtype', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'sub_model', 'label' => 'Sub Model', 'align' => 'left', 'field' => 'sub_model', 'sortable' => true, 'style' => 'font-size:16px;'],
        ];

        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch(['carname'], $search);
            }
        }

        $join = "";
        $addfields = "";
        $condition = " where 1=1";

        $join = " left join cmodel as model on model.carid=cmake.id ";
        $addfields = ", model.model as model, model.cryear, model.crtype, model.sub_model, model.line as cmodelline";

        $qry = "select cmake.id as carid, cmake.carname as cmake $addfields from cmake $join $condition " . $filtersearch . " order by carname";
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }

    public function lookupcallback($config)
    {
        $clientid = $config['params']['tableid'];
        $row = $config['params']['rows'];
        $returndata = [];
        $status = true;
        $msg = 'Successfully added.';

        foreach ($row as $key2 => $value) {
            $config['params']['row']['line']         = 0;
            $config['params']['row']['clientid']     = $clientid;
            $config['params']['row']['carid']        = $row[$key2]['carid'];
            $config['params']['row']['cmodelline']   = $row[$key2]['cmodelline'];
            $config['params']['row']['cmake']        = $row[$key2]['cmake'];
            $config['params']['row']['model']        = $row[$key2]['model'];
            $config['params']['row']['cryear']       = $row[$key2]['cryear'];
            $config['params']['row']['crtype']       = $row[$key2]['crtype'];
            $config['params']['row']['sub_model']    = $row[$key2]['sub_model'];
            $config['params']['row']['licenseno']    = '';
            $config['params']['row']['mileage']      = 0;
            $config['params']['row']['carengine']    = '';
            $config['params']['row']['transmission'] = '';
            $config['params']['row']['motorno']      = '';
            $config['params']['row']['chassis']      = '';
            $config['params']['row']['mvno']         = '';
            $config['params']['row']['insurance']    = '';
            $config['params']['row']['labor']        = 0;
            $config['params']['row']['bgcolor']      = '';

            $return = $this->save($config);
            if ($return['status']) {
                array_push($returndata, $return['row'][0]);
            } else {
                $status = false;
                $msg = $return['msg'];
            }
        }

        return ['status' => $status, 'msg' => $msg, 'data' => $returndata];
    } // end function

    public function lookuplogs($config)
    {
        $doc = 'CUSTOMER';
        $lookupsetup = array(
            'type' => 'show',
            'title' => 'Vehicle Master Logs',
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
