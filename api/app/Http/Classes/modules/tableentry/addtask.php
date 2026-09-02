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
use App\Http\Classes\tableentryClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class addtask
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Add Tasks';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'pttask';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $othersClass;
    public $style = 'width:100%;max-width:1000px;';
    private $fields = ['pttrno', 'jobline', 'cost', 'mechanic', 'rem'];
    public $showclosebtn = true;
    private $reporter;
    private $logger;


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
        $cardtype = 0;
        $dlock = 1;
        $isinactive = 2;

        $columns = ['action', 'jobcode', 'description', 'cost', 'mechanic', 'rem'];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $columns]];

        $stockbuttons = ['delete', 'save'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$cost]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][$mechanic]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$rem]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";

        $obj[0][$this->gridname]['columns'][$jobcode]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$description]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$cost]['readonly'] = false;
        return $obj;
    }


    public function createtabbutton($config)
    {
        $tbuttons = ['saveallentry', 'additem'];

        foreach ($tbuttons as $key => $value) {
            $$value = $key;
        }
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[$additem]['lookupclass'] = 'addtask';
        $obj[$additem]['action'] = 'lookupsetup';

        return $obj;
    }

    public function add($config)
    {
        $data = [];
        $data['line'] = 0;
        $data['pttrno'] = 0;
        $data['jobline'] = 0;
        $data['jobcode'] = '';
        $data['description'] = '';
        $data['cost'] = '';
        $data['mechanic'] = '';
        $data['rem'] = '';
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    private function selectqry()
    {
        $qry = "task.line,job.jobcode,job.description";
        foreach ($this->fields as $key => $value) {

            $qry = $qry . ',task.' . $value;
        }
        return $qry;
    }

    public function saveallentry($config)
    {
        $data = $config['params']['data'];

        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $data[$key][$value2];
                }


                $current_timestamp = $this->othersClass->getCurrentTimeStamp();
                if ($data[$key]['line'] == 0) {
                    $line = $this->coreFunctions->insertGetId($this->table, $data2);
                    $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . $data[$key]['cardtype']);
                } else {
                    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['editby'] = $config['params']['user'];
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'Saved all Successfully', 'data' => $returndata];
    } // end function 
    public function save($config)
    {
        $companyid = $config['params']['companyid'];
        $row = $config['params']['row'];

        $dateTables = ['pttask'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

        $data = [];
        foreach ($this->fields as $key2 => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfieldFast($value, $row[$value], $lookups);
        }

        if ($row['line'] == 0) { // insert
            $data['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
            $data['encodedby'] = $config['params']['user'];
            $line = $this->coreFunctions->insertGetId($this->table, $data);
            if ($line != 0) {
                $config['params']['doc'] = 'ENTRYJOBDETAILS';
                $job = $this->coreFunctions->opentable("select jobcode,description from jobtask where line = " . $data['jobline']);
                $this->logger->sbcmasterlog($line, $config, ' CREATE - Line: ' . $line . ' Job Code : ' . $job[0]->jobcode . ' ' . 'Job Desc : ' . $job[0]->description);
                $returnrow = $this->loaddataperrecord($line);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else { // update
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            $update = $this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]);
            if ($update) {
                $returnrow = $this->loaddataperrecord($row['line']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Update failed.'];
            }
        }
    }
    public function delete($config)
    {
        $row = $config['params']['row'];
        $qry = "delete from " . $this->table . " where line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
        $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE - ' . 'Job Code' . $row['jobcode'] . '' . ' , Job Desc: ' . $row['description']);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }

    public function loaddata($config)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " as task
            left join jobtask as job on job.line = task.jobline
            order by task.line";
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }


    public function loaddataperrecord($line)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " as task
            left join jobtask as job on job.line = task.jobline
            where task.line = " . $line . "
            order by task.line";
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }
    public function lookupsetup($config)
    {

        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'addtask':
                return $this->addtask($config);
                break;

            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
                break;
        }
    }
    public function addtask($config)
    {
        $lookupsetup = array(
            'type' => 'multi',
            'rowkey' => 'line',
            'title' => 'List of Tasks / Labor',
            'style' => 'width:800px;max-width:800px;'
        );

        $plotsetup = array(
            'plottype' => 'tableentry',
            'action' => 'addtogrid'
        );

        // lookup columns
        $cols = [
            ['name' => 'jobcode', 'label' => 'Job Code', 'align' => 'left', 'field' => 'jobcode', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'description', 'label' => 'Description', 'align' => 'left', 'field' => 'description', 'sortable' => true, 'style' => 'font-size:16px;']

        ];
        $qry = "select line as jobline,jobcode,description from jobtask";
        $data = $this->coreFunctions->opentable($qry);

        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }
    public function lookupcallback($config)
    {
        $rows = $config['params']['rows'];
        $pttrno = $config['params']['tableid'];

        $returndata = [];
        $status = true;
        $msg = 'Successfully added.';
        foreach ($rows  as $key2 => $value) {
            $config['params']['row']['line'] = 0;
            $config['params']['row']['pttrno'] = $pttrno;
            $config['params']['row']['jobline'] = $rows[$key2]['jobline'];
            $config['params']['row']['rem'] = '';
            $config['params']['row']['mechanic'] = '';
            $config['params']['row']['cost'] = 0;
            $config['params']['row']['bgcolor'] = 'bg-blue-2';
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
} //end class
