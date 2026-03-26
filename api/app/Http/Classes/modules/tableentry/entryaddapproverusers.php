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
use App\Http\Classes\sbcdb\trigger;
use App\Http\Classes\sbcdb\waims;
use App\Http\Classes\sbcdb\customersupport;
use Symfony\Component\VarDumper\VarDumper;

class entryaddapproverusers
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'APPROVERS';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'approverdetails';
    private $othersClass;
    public $style = 'width:50%;';
    private $fields = ['appline', 'approver', 'ordernum'];
    public $showclosebtn = true;
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    public $logger;

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
        $attrib = array(
            'load' => 0
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $tab = [$this->gridname => ['gridcolumns' => ['action', 'approver', 'ordernum']]];

        $stockbuttons = ['delete'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][1]['style'] = 'width: 150px;whiteSpace: normal;min-width:10px;max-width:150px;';
        $obj[0][$this->gridname]['columns'][1]['type'] = 'label';

        return $obj;
    }


    public function createtabbutton($config)
    {
        $tbuttons = ['adduserapprover', 'saveallentry', 'whlog'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    private function selectqry($config)
    {
        $line = isset($config['params']['row']['line']) ? $config['params']['row']['line'] : 0;
        $qry = "select line," . $line . " as appline, approver, ordernum 
                from approverdetails";
        return $qry;
    }

    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        foreach ($data as $key => $value) {
            $data2 = [];

            foreach ($this->fields as $key2 => $value2) {
                $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
            }

            if ($data[$key]['line'] == 0) {
                $data2['createdate'] = $this->othersClass->getCurrentTimeStamp();
                $data2['createby'] = $config['params']['user'];
                $this->coreFunctions->sbcinsert($this->table, $data2);
            } else {
                if ($data2['appline'] == 0) {
                    $data2['appline'] = $config['params']['sourcerow']['line'];
                }
                $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
            }
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
    } // end function 

    public function save($action, $config)
    {
        $data = [];
        $data2 = [];

        $row = $config['params']['row'];
        if ($action == 'insert') {
            $data = [
                'appline' => $row['appline'],
                'approver' => $row['approver'],
                'createdate' => $this->othersClass->getCurrentTimeStamp(),
                'createby' => $config['params']['user']
            ];
            if ($this->coreFunctions->sbcinsert($this->table, $data) == 1) {
                $line = $this->coreFunctions->getfieldvalue($this->table, 'line', 'appline=? and approver=?', [$row['appline'], $row['approver']]);
                $row = $this->loaddataperrecord($line, $row['appline']);
            }
        }

        return ['status' => true, 'msg' => 'Items were successfully added.', 'rows' => $row, 'line' => $line, 'reloaddata' => true];
    } //end function

    public function delete($config)
    {
        $row = $config['params']['row'];
        $qry = "delete from " . $this->table . " where line=? ";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);

        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }

    private function loaddataperrecord($line, $appid)
    {
        $qry = "select line," . $appid . " as appline, approver, ordernum 
                from approverdetails
                where appline=? and line= ? order by ordernum";
        $data = $this->coreFunctions->opentable($qry, [$appid, $line]);
        return $data;
    }

    public function loaddata($config)
    {
        $line = isset($config['params']['row']['line']) ? $config['params']['row']['line'] : 0;
        if ($line == 0) {
            $line = $config['params']['data'][0]['appline'];
        }
        $qry = "select line," . $line . " as appline, approver, ordernum 
                from approverdetails where appline=? order by ordernum";
        $data = $this->coreFunctions->opentable($qry, [$line]);
        return $data;
    }

    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'whlog':
                return $this->lookuplogs($config);
                break;
            case 'adduserapprover':
                return $this->adduserapprover($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
                break;
        }
    }

    public function adduserapprover($config)
    {
        $lookupsetup = array(
            'type' => 'single',
            'rowkey' => 'keyid',
            'title' => 'List of Users',
            'style' => 'width:800px;max-width:800px;'
        );

        $plotsetup = array(
            'plottype' => 'multientry',
            'action' => 'addtogrid'
        );

        // lookup columns
        $cols = array(
            array('name' => 'approver', 'label' => 'Username', 'align' => 'left', 'field' => 'approver', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'name', 'label' => 'Name', 'align' => 'left', 'field' => 'name', 'sortable' => true, 'style' => 'font-size:16px;')
        );


        $appid = $config['params']['sourcerow']['line'];
        $qry = "select userid as keyid,userid,$appid as appline,0 as ordernum, username as approver,name,0 as line from useraccess";
        $data = $this->coreFunctions->opentable($qry);

        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }

    public function lookupcallback($config)
    {
        $row = $config['params']['row'];
        $config['params']['row']['appline'] = $row['appline'];
        $config['params']['row']['approver'] = $row['approver'];
        $config['params']['row']['ordernum'] = 0;
        $config['params']['row']['line'] = 0;
        $return = $this->save('insert', $config);

        return ['status' => true, 'msg' => 'Successfully added.', 'data' => $return['rows'][0]];
    } // end function

    public function lookuplogs($config)
    {
        $doc = strtoupper($config['params']['lookupclass']);
        $lookupsetup = array(
            'type' => 'show',
            'title' => 'List of Logs',
            'style' => 'width:1000px;max-width:1000px;'
        );

        // lookup columns
        $cols = array(
            array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

        );

        $trno = strtoupper($config['params']['sourcerow']['line']);

        $qry = "select trno, doc, task, log.user, dateid, 
                if(pic='','blank_user.png',pic) as pic
                from " . $this->tablelogs . " as log
                left join useraccess as u on u.username=log.user
                where log.doc = '" . $doc . "' and trno = $trno
                union all
                select trno, doc, task, log.user, dateid, 
                if(pic='','blank_user.png',pic) as pic
                from  " . $this->tablelogs_del . " as log
                left join useraccess as u on u.username=log.user
                where log.doc = '" . $doc . "' and trno = $trno ";

        $qry = $qry . " order by dateid desc";
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
    }
} //end class
