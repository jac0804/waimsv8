<?php

namespace App\Http\Classes\modules\kwhmonitoring;

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

class entrysubpowercat
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Sub-Category (Level 1)';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'subpowercat';
    private $othersClass;
    private $logger;
    public $style = 'width:100%;';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';

    private $fields = ['name', 'catid'];
    public $showclosebtn = false;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 4056);
        return $attrib;
    }


    public function createTab($config)
    {

        $this->modulename = $config['params']['row']['name'] . ' - Sub-Category (Level 1)';

        $action = 0;
        $name = 1;

        $tab = [$this->gridname => ['gridcolumns' => ['action',  'name']]];

        $stockbuttons = ['save', 'delete', 'addsubpowercat2'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:10%;whiteSpace: normal;min-width:10%;";
        $obj[0][$this->gridname]['columns'][$name]['style'] = "width:90%;whiteSpace: normal;min-width:90%;";

        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }


    public function createtabbutton($config)
    {
        $tbuttons = ['addrecord', 'saveallentry'];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }


    public function add($config)
    {
        $data = [];
        $data['line'] = 0;
        $data['catid'] = $config['params']['sourcerow']['line'];
        $data['name'] = '';
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    private function selectqry()
    {
        $qry = "line,catid,name";
        return $qry;
    }

    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }
                if ($data[$key]['line'] == 0) {
                    $data2['createby'] = $config['params']['user'];
                    $data2['createdate'] = $this->othersClass->getCurrentTimeStamp();
                    $line = $this->coreFunctions->insertGetId($this->table, $data2);
                    $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . $data[$key]['name']);
                } else {
                    $data2['editby'] = $config['params']['user'];
                    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
    } // end function    

    public function save($config)
    {
        $data = [];
        $row = $config['params']['row'];
        $code = $row['name'];


        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }

        if ($row['line'] == 0) {
            $data['createby'] = $config['params']['user'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $line = $this->coreFunctions->insertGetId($this->table, $data);
            $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . $row['name']);
            if ($line != 0) {
                $returnrow = $this->loaddataperrecord($line);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {
            $data['editby'] = $config['params']['user'];
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
                $returnrow = $this->loaddataperrecord($row['line']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        }
    } //end function

    public function delete($config)
    {
        $row = $config['params']['row'];

        $qry = "select distinct name from subpowercat2 where subcatid=?";
        $count = $this->coreFunctions->opentable($qry, [$row['line']]);
        if (!empty($count)) {
            return ['status' => false, 'msg' => 'Already used in sub category...' . $count[0]->name];
        }

        $qry = "delete from " . $this->table . " where line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
        $this->logger->sbcmasterlog($row['line'], $config, ' DELETE - LINE: ' . $row['line'] . '' . ', Name: ' . $row['name']);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }


    private function loaddataperrecord($line)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " where line=?";
        $data = $this->coreFunctions->opentable($qry, [$line]);
        return $data;
    }

    public function loaddata($config)
    {
        if (isset($config['params']['sourcerow']['line'])) {
            $catid = $config['params']['sourcerow']['line'];
        } else {
            $catid = $config['params']['row']['line'];
        }

        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " where catid=? order by line";
        $data = $this->coreFunctions->opentable($qry, [$catid]);
        return $data;
    }


    public function lookupsetup($config)
    {
        return $this->lookupwh($config);
    }

    public function lookupwh($config)
    {
        //default
        $plotting = array('warehouse' => 'client');
        $plottype = 'plotgrid';
        $title = 'List of Warehouse';
        $lookupsetup = array(
            'type' => 'single',
            'title' => $title,
            'style' => 'width:900px;max-width:900px;'
        );
        $plotsetup = array(
            'plottype' => $plottype,
            'action' => '',
            'plotting' => $plotting
        );
        // lookup columns
        $cols = array();
        $col = array('name' => 'client', 'label' => 'Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;');
        array_push($cols, $col);
        $col = array('name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;');
        array_push($cols, $col);

        $qry = "select client,clientname from client where iswarehouse=1 order by client";
        $data = $this->coreFunctions->opentable($qry);
        $index = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
    } // end function




























} //end class
