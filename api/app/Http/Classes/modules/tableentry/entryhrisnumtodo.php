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

class entryhrisnumtodo
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'TO DO';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'hrisnumtodo';
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['line', 'trno', 'clientid', 'userid'];
    public $showclosebtn = true;
    private $lookupclass;


    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->lookupclass = new lookupclass;
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
        $itemid = $config['params']['tableid'];
        $item = $this->othersClass->getitemname($itemid);
        $this->modulename = $this->modulename;

        $tab = [$this->gridname => ['gridcolumns' => ['createby', 'usertype', 'createdate', 'donedate']]];

        $stockbuttons = [];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][0]['type'] = "label";
        $obj[0][$this->gridname]['columns'][1]['type'] = "label";
        $obj[0][$this->gridname]['columns'][2]['type'] = "label";
        $obj[0][$this->gridname]['columns'][3]['type'] = "label";

        $obj[0][$this->gridname]['columns'][0]['label'] = "Assigned By";
        $obj[0][$this->gridname]['columns'][1]['label'] = "Assigned To";
        $obj[0][$this->gridname]['columns'][2]['label'] = "Assigned Date";

        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['assignuser'];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }

    public function add($config)
    {
        $id = $config['params']['tableid'];
        $data = [];
        $data['line'] = 0;
        $data['trno'] = $id;
        $data['userid'] = 0;
        $data['clientid'] = 0;
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
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $line = $this->coreFunctions->insertGetId($this->table, $data);

            if ($line != 0) {
                $returnrow = $this->loaddataperrecord($row['trno'], $line);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {
            if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
                $returnrow = $this->loaddataperrecord($row['trno'], $row['line']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        }
    } //end function

    public function delete($config)
    {
        $row = $config['params']['row'];
        $qry = "delete from " . $this->table . " where trno=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line']]);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }

    private function loaddataperrecord($trno, $line)
    {

        $select = "line, trno,t.createby, t.createdate, t.clientid, t.userid, 
    case when t.donedate is null then '' else t.donedate end as donedate,
    case when t.userid <> 0 then ast.name else c.clientname end as usertype";
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " 
    from transnumtodo as t
    left join useraccess as u on t.createby = u.userid
    left join useraccess as ast on ast.userid = t.userid
    left join client as c on c.clientid = t.clientid
    where trno=? and line=? order by line";

        $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
        return $data;
    }

    public function loaddata($config)
    {
        $tableid = $config['params']['tableid'];
        $center = $config['params']['center'];
        $select = "line, trno, t.createby, t.createdate, t.clientid, t.userid, 
    case when t.donedate is null then '' else t.donedate end as donedate,
    case when t.userid <> 0 then ast.name else c.clientname end as usertype";
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " 
    from transnumtodo as t
    left join useraccess as u on t.createby = u.userid
    left join useraccess as ast on ast.userid = t.userid
    left join client as c on c.clientid = t.clientid
    where trno=? order by line";
        $data = $this->coreFunctions->opentable($qry, [$tableid]);
        return $data;
    }

    public function lookupsetup($config)
    {
        return $this->lookupclass->assignuser($config);
    }

    public function lookupcallback($config)
    {
        $id = $config['params']['tableid'];
        $row = $config['params']['rows'];
        $data = [];
        $returndata = [];

        foreach ($row  as $key2 => $value) {
            if ($row[$key2]['userid'] == 0) {
                $user = $row[$key2]['clientid'];
            } else {
                $user = $row[$key2]['userid'];
            }

            $qry = "select createby,createdate,clientid,userid
              from transnumtodo
              where trno=? and ((userid = ? and clientid=0) or (userid=0 and clientid = ?)) and donedate is null";

            $data = $this->coreFunctions->opentable($qry, [$id, $user, $user]);
            if (empty($data)) {
                $config['params']['row']['line'] = 0;
                $config['params']['row']['trno'] = $id;
                $config['params']['row']['userid'] = $row[$key2]['userid'];
                $config['params']['row']['clientid'] = $row[$key2]['clientid'];
                $config['params']['row']['bgcolor'] = 'bg-blue-2';
                $return = $this->save($config);
                if ($return['status']) {
                    array_push($returndata, $return['row'][0]);
                }
            } else {
                return ['status' => false, 'msg' => 'User already assigned...', 'data' => $returndata];
            }
        }
        return ['status' => true, 'msg' => 'Successfully added.', 'data' => $returndata];
    } // end function



} //end class
