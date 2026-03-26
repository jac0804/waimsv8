<?php

namespace App\Http\Classes\modules\hmsentry;

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
use App\Http\Classes\lookup\hmslookup;

class entryroomlist
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'ROOM LIST';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'hmsrooms';
    private $othersClass;
    public $style = 'width:100%;';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'masterfile_log';
    private $fields = ['roomno', 'roomtypeid', 'isinactive'];
    public $showclosebtn = true;
    private $enrollmentlookup;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->enrollmentlookup = new hmslookup;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createTab($config)
    {
        $tab = [$this->gridname => ['gridcolumns' => ['action', 'roomno', 'isinactive']]];

        $stockbuttons = ['save', 'delete'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][0]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";

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
        $data['roomtypeid'] = $config['params']['tableid'];
        $data['line'] = 0;
        $data['roomno'] = '';
        $data['isinactive'] = 'false';
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
        $data['roomtypeid'] = $config['params']['tableid'];

        if ($data['roomno'] == '') {
            $data[0]['bgcolor'] = 'bg-red-2';
            $data[0]['line'] = $row['line'];
            return ['status' => false, 'msg' => 'Invalid description', 'row' => $data];
        }

        if ($row['line'] == 0) {
            $line = $this->coreFunctions->insertGetId($this->table, $data);
            $this->logger->sbcmasterlog($data['roomtypeid'], $config, ' CREATE ROOM NO. - ' . $row['roomno']);
            if ($line) {
                $returnrow = $this->loaddataperrecord($data['roomtypeid'], $line, $config);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            if ($this->coreFunctions->sbcupdate($this->table, $data, ['roomtypeid' => $data['roomtypeid'], 'line' => $row['line']]) == 1) {
                $returnrow = $this->loaddataperrecord($data['roomtypeid'], $row['line'], $config);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        }
    } //end function

    public function saveallentry($config)
    {
        $id = $config['params']['tableid'];
        $row = $config['params']['data'];
        $data = [];
        $msg = 'Successfully added.';
        $status = true;
        foreach ($row  as $key2 => $value) {
            $config['params']['row']['line'] = $value['line'];
            $config['params']['row']['roomtypeid'] = $id;
            $config['params']['row']['roomno'] = $value['roomno'];
            $config['params']['row']['isinactive'] = $value['isinactive'];
            $config['params']['row']['bgcolor'] = 'bg-blue-2';
            $return = $this->save($config);

            if ($return['status']) {
                array_push($data, $return['row'][0]);
            } else {
                array_push($data, $return['row'][0]);
                $msg = $return['msg'] . '....';
                $status = false;
            }
        }

        return ['status' => $status, 'msg' => $msg, 'data' => $data];
    }

    public function delete($config)
    {
        $row = $config['params']['row'];
        $trno = $config['params']['tableid'];
        $qry = "delete from " . $this->table . " where roomtypeid=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$trno, $row['line']]);
        $this->logger->sbcdelmaster_log($row['roomtypeid'], $config, 'REMOVE ROOM NO. - ' . $row['roomno']);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }

    public function deleteallitem($config)
    {
        $trno = $config['params']['tableid'];
        $qry = "delete from " . $this->table . " where roomtypeid=?";
        $this->coreFunctions->execqry($qry, 'delete', [$trno]);
        return ['status' => true, 'msg' => 'Successfully deleted.', 'data' => []];
    }

    private function selectqry()
    {
        $qry = "line, roomno, roomtypeid, 
        (case when isinactive=0 then 'false' else 'true' end) as isinactive";
        return $qry;
    }

    private function loaddataperrecord($trno, $line)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " where roomtypeid=? and line=?";
        $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
        return $data;
    }

    public function loaddata($config)
    {
        $trno = $config['params']['tableid'];
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " where roomtypeid=? 
        order by line";
        $data = $this->coreFunctions->opentable($qry, [$trno]);
        return $data;
    }
} //end class
