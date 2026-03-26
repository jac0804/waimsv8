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

class  tabhelper
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'HELPER';
    public $tablenum = 'cntnum';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'cntclient';
    private $htable = 'hcntclient';
    private $stock = '';
    private $hstock = '';
    private $othersClass;
    public $style = 'width:100%;max-width: 100%';
    private $fields = ['trno', 'line', 'clientid', 'rem'];
    public $showclosebtn = true;
    public $tablelogs = 'table_log';
    public $tablelogs_del = 'del_table_log';
    private $logger;


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
        $attrib = ['load' => 0];
        return $attrib;
    }

    public function createTab($config)
    {
        $hidebuttons = false;

        $trno = $config['params']['tableid'];
        $isposted = $this->othersClass->isposted2($trno, "cntnum");


        $columns = ['action', 'clientname'];
        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $columns]];
        $stockbuttons = ['delete'];

        if ($isposted) {
            $hidebuttons = true;
            $stockbuttons = [];
        }

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:50px;whiteSpace: normal;min-width:50px;";
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:450px;whiteSpace: normal;min-width:450px;';

        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Helper Name';
        $obj[0][$this->gridname]['columns'][$clientname]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'input';
        if ($hidebuttons) {
            $obj[0][$this->gridname]['columns'][$action]['type'] = 'coldel';
        }

        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['additem', 'saveallentry'];
        $trno = $config['params']['tableid'];
        $isposted = $this->othersClass->isposted2($trno, "cntnum");

        if ($isposted) {
            $tbuttons = [];
        }
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[0]['action'] = "lookupsetup";
        $obj[0]['action2'] = "lookupsetup";
        $obj[0]['icon'] = "person_add";
        $obj[0]['label'] = "ADD HELPER";
        return $obj;
    }


    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'additem':
                return $this->lookupaddhelper($config);
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

    public function lookupaddhelper($config)
    {
        $trno = $config['params']['tableid'];

        $lookupsetup = array(
            'type' => 'single',
            'title' => 'List of Helpers',
            'style' => 'width:900px;max-width:900px;'
        );

        $plotsetup = array(
            'plottype' => 'callback',
            'action' => 'addtogrid'
        );
        // lookup columns
        $cols = [];
        array_push($cols, array('name' => 'client', 'label' => 'Helper Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'));
        array_push($cols, array('name' => 'clientname', 'label' => 'Helper Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'));
        array_push($cols, array('name' => 'address', 'label' => 'Address', 'align' => 'left', 'field' => 'address', 'sortable' => true, 'style' => 'font-size:16px;'));

        $qry = "select " . $trno . " as trno, '0' as line, clientid, 
                        client, clientname, addr as address,'' as rem
                from client where ispassenger = 1 and isinactive = 0";
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    } //end function

    public function save($action, $config)
    {
        $data = [];
        $row = $config['params']['row'];
        $trno = $config['params']['tableid'];

        $data = [
            'trno' => $trno,
            'line' => $row['line'],
            'clientid' => $row['clientid'],
            'rem' => $row['rem']
        ];
        foreach ($data as $key => $value) {
            $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        }

        $this->coreFunctions->sbcupdate("cntnum", ['statid' => 0], ['trno' => $trno]);

        $existhelper = $this->coreFunctions->getfieldvalue("cntclient", "clientid", "clientid=? and trno=?", [$data['clientid'], $trno]);

        if ($existhelper == 0) {
            if ($action == 'insert') {
                $qry = "select line as value from " . $this->table . " where trno=? order by line desc limit 1";
                $line = $this->coreFunctions->datareader($qry, [$trno]);
                if ($line == '') {
                    $line = 0;
                }
                $line = $line + 1;
                $data['line'] = $line;
                $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
                $data['createby'] = $config['params']['user'];
                $data['ishelper'] = 1;

                if ($this->coreFunctions->sbcinsert($this->table, $data)) {
                    $config['params']['row']['trno'] = $trno;
                    $data =  $this->loaddataperrecord($config, $data['line']);
                    $this->logger->sbcwritelog($trno, $config, ' CREATE - HELPER', $row['clientname']);
                    return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $data];
                } else {
                    return ['status' => false, 'msg' => 'Saving failed.', 'data' => []];
                }
            }
        } else {
            return ['status' => false, 'msg' => 'There was already a helper named ' . $row['clientname'] . '.', 'data' => []];
        }
    } //end function



    public function saveallentry($config)
    {
        $trno = $config['params']['tableid'];
        $this->coreFunctions->sbcupdate("cntnum", ['statid' => 0], ['trno' => $trno]);

        $data = $config['params']['data'];

        foreach ($data as $key => $value) {
            $data2 = [];

            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }
                $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                $data2['editby'] = $config['params']['user'];

                if ($data[$key]['line'] != 0) {
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line'], 'trno' => $data[$key]['trno']]);
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata, 'reloadhead' => true];
    } // end function $$

    public function delete($config)
    {
        $trno = $config['params']['tableid'];
        $this->coreFunctions->sbcupdate("cntnum", ['statid' => 0], ['trno' => $trno]);
        $row = $config['params']['row'];
        $this->coreFunctions->LogConsole($row);

        $qry = "delete from " . $this->table . " where trno=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line']]);
        $this->logger->sbcdel_log($row['trno'], $config, 'REMOVE - HELPER', $row['clientname']);
        return ['status' => true, 'msg' => 'Successfully deleted.', 'reloadhead' => true];
    }

    private function selectqry($config)
    {
        return " select head.line, head.trno, head.clientid, c.clientname,c.addr as address,head.rem ";
    }
    private function loaddataperrecord($config, $line)
    {
        $trno = $config['params']['row']['trno'];
        $sqlselect = $this->selectqry($config);
        $qry = $sqlselect . ",'' as bgcolor from " . $this->table . " as head
                left join client as c on c.clientid = head.clientid
                where head.trno = $trno and head.line = $line and ishelper=1 order by line";
        $this->coreFunctions->LogConsole($qry);
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }

    public function loaddata($config)
    {
        $trno = $config['params']['tableid'];

        $sqlselect = $this->selectqry($config);
        $qry = $sqlselect . ",'' as bgcolor from " . $this->table . " as head
                left join client as c on c.clientid = head.clientid
                where head.trno = ? and ishelper=1 
                union all ";
        $qry = $qry . " " . $sqlselect . ",'' as bgcolor from " . $this->htable . " as head
                left join client as c on c.clientid = head.clientid
                where head.trno = ? and ishelper=1 
                order by line";

        $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
        return $data;
    }
} //end class
