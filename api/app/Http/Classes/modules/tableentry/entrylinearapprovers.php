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
use App\Http\Classes\builder\lookupclass;

class entrylinearapprovers
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'LINEAR APPROVERS';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $logger;
    private $table = 'approversetup';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['doc', 'isapprover'];
    public $showclosebtn = false;
    private $lookupclass;


    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->lookupclass = new lookupclass;
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
        $action = 0;
        $clientname = 1;
        $moduletype = 2;
        $isapprover = 3;
        $tab = [$this->gridname => ['gridcolumns' => ['action', 'moduletype', 'isapprover']]];

        $stockbuttons = ['save', 'delete', 'addapproverusers']; //addapprovercat

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:15%;whiteSpace: normal;min-width:15%;";
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = "width:40%;whiteSpace: normal;min-width:40%;";
        $obj[0][$this->gridname]['columns'][$moduletype]['style'] = "width:35%;whiteSpace: normal;min-width:35%;";


        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['assignmodule', 'saveallentry', 'whlog'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[0]['label'] = 'ADD MODULE';
        return $obj;
    }

    public function add($config)
    {
        $id = $config['params']['sourcerow']['line'];
        $data = [];
        $data['line'] = 0;
        $data['moduletype'] = '';
        $data['doc'] = '';
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    public function save($config)
    {
        $data = [];
        $row = $config['params']['row'];
        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }
        if ($row['line'] == 0) {
            $this->coreFunctions->insertGetId($this->table, $data);
            $line = $this->coreFunctions->getfieldvalue("approversetup", "line", "doc = ?", [$row['doc']]);
            $returnrow = $this->loaddataperrecord($config, $line);
            return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
        } else {
            if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
                $returnrow = $this->loaddataperrecord($config, $row['line']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        }
    } //end function

    public function delete($config)
    {
        $row = $config['params']['row'];
        $qry = "delete from " . $this->table . " where line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);

        $this->logger->sbcmasterlog($row['line'], $config, ' REMOVE -' . $row['moduletype']);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }

    private function loaddataperrecord($config, $line)
    {
        $qry = "select line,doc as moduletype,doc,'' as bgcolor,case when isapprover= 1 then 'true' else 'false' end as isapprover 
                from approversetup where line = $line";
        return $this->coreFunctions->opentable($qry);
    }

    public function loaddata($config)
    {
        $qry = "select line,doc as moduletype,doc,'' as bgcolor,case when isapprover= 1 then 'true' else 'false' end as isapprover 
                from approversetup";
        $data = $this->coreFunctions->opentable($qry);
        return $data;
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
                    $this->coreFunctions->insertGetId($this->table, $data2);
                    $this->logger->sbcmasterlog($data[$key]['line'], $config, ' CREATE - ' . $data[$key]['doc']);
                } else {
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
                    $this->logger->sbcmasterlog($data[$key]['line'], $config, ' UPDATE - ' . $data[$key]['doc']);
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
    } // end function

    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'whlog':
                return $this->lookuplogs($config);
                break;
            case 'assignmodule':
                return $this->assignmodule($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup'];
                break;
        }
    }

    public function assignmodule($config)
    {
        $lookupsetup = array(
            'type' => 'multi',
            'rowkey' => 'keyid',
            'title' => 'List of Module',
            'style' => 'width:800px;max-width:800px;'
        );

        $plotsetup = array(
            'plottype' => 'tableentry',
            'action' => 'addtogrid'
        );

        // lookup columns
        $cols = array(
            array('name' => 'moduletype', 'label' => 'Module', 'align' => 'left', 'field' => 'moduletype', 'sortable' => true, 'style' => 'font-size:16px;'),
        );

        $qry = "
        select 'PR' as moduletype, 1 as keyid
        union all
        select 'PO' as moduletype, 2 as keyid
        union all
        select 'RR' as moduletype, 3 as keyid
        union all
        select 'DM' as moduletype, 4 as keyid
        union all
        select 'SO' as moduletype, 5 as keyid
        union all
        select 'SJ' as moduletype, 6 as keyid
        union all
        select 'CM' as moduletype, 7 as keyid";
        $data = $this->coreFunctions->opentable($qry);

        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }

    public function lookupcallback($config)
    {
        $id = $config['params']['tableid'];
        $row = $config['params']['rows'];
        $data = [];
        $returndata = [];
        $this->othersClass->logConsole(json_encode($row));
        foreach ($row  as $key2 => $value) {
            $config['params']['row']['line'] = 0;
            $config['params']['row']['doc'] = $row[$key2]['moduletype'];
            $config['params']['row']['bgcolor'] = 'bg-blue-2';
            $config['params']['row']['isapprover'] = 0;
            $return = $this->save($config);
            if ($return['status']) {
                array_push($returndata, $return['row'][0]);
            }
        }
        return ['status' => true, 'msg' => 'Successfully added.', 'data' => $returndata];
    } // end function

    public function lookuplogs($config)
    {
        $doc = $config['params']['doc'];
        $lookupsetup = array(
            'type' => 'show',
            'title' => 'Item Sub Category Master Logs',
            'style' => 'width:1000px;max-width:1000px;'
        );

        // lookup columns
        $cols = array(
            array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')
        );

        $trno = $config['params']['tableid'];

        $qry = "select trno, doc, task, log.user, dateid, 
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
} //end class
