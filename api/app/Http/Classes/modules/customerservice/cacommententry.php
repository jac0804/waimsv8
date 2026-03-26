<?php

namespace App\Http\Classes\modules\customerservice;

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


class cacommententry
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'COMMENT SECTION';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;

    public $table = 'csscomment';
    public $htable = 'hcsscomment';

    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['comment'];
    public $tablelogs = 'transnum_log';
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
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createTab($config)
    {
        $isposted = $this->othersClass->isposted2($config['params']['tableid'], "transnum");
        $column = ['action', 'comment', 'createdate', 'createby'];
        $stockbuttons = ['save'];
        if ($isposted) {
            array_shift($column);
            $stockbuttons = [];
        }
        $tab = [$this->gridname => ['gridcolumns' => $column]];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][2]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][3]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";

        $obj[0][$this->gridname]['columns'][2]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][3]['readonly'] = true;

        if ($isposted) {
            $obj[0][$this->gridname]['columns'][0]['readonly'] = true; //comment
            $obj[0][$this->gridname]['columns'][1]['readonly'] = true;
        } else {
            $obj[0][$this->gridname]['columns'][1]['checkfield'] = 'ispa';
            $obj[0][$this->gridname]['columns'][0]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
        }
        return $obj;
    }
    public function createtabbutton($config)
    {
        $isposted = $this->othersClass->isposted2($config['params']['tableid'], "transnum");
        if ($isposted) {
            $tbuttons = [];
        } else {
            $tbuttons = ['addrecord', 'saveallentry'];
        }
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[2]['label'] = 'Delete all';
        $obj[2]['lookupclass'] = 'loaddata';
        return $obj;
    }
    public function add($config)
    {
        $data = [];
        $data['trno'] = $config['params']['tableid'];
        $data['line'] = 0;
        $data['comment'] = '';
        $data['createdate'] = '';
        $data['createby'] = '';
        $data['ispa'] = 0;
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
        $data['trno'] = $config['params']['tableid'];

        if ($data['comment'] == '') {
            $data[0]['bgcolor'] = 'bg-red-2';
            $data[0]['line'] = $row['line'];
            return ['status' => false, 'msg' => 'Invalid description', 'row' => $data];
        }

        if ($row['line'] == 0) {
            $qry = "select line as value from " . $this->table . " where trno=? order by line desc limit 1";
            $line = $this->coreFunctions->datareader($qry, [$data['trno']]);
            if (!$line) {
                $line = 0;
            }
            $line = $line + 1;
            $data["line"] = $line;
            $current_timestamp = $this->othersClass->getCurrentTimeStamp();
            $user = $config['params']['user'];
            $data['createdate'] = $current_timestamp;
            $data['createby'] = $user;
            if ($this->coreFunctions->sbcinsert($this->table,  $data)) {
                $returnrow = $this->loaddataperrecord($data['trno'], $line, $config);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        }
    } //end function

    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }
                $current_timestamp = $this->othersClass->getCurrentTimeStamp();
                $user = $config['params']['user'];
                $data2['createdate'] = $current_timestamp;
                $data2['createby'] = $user;
                $data2['trno'] = $config['params']['tableid'];
                if ($data[$key]['line'] == 0) {
                    $line = $this->coreFunctions->insertGetId($this->table, $data2);
                    $qry = "select line as value from " . $this->table . " where trno=? order by line desc limit 1";
                    $checkline = $this->coreFunctions->datareader($qry, [$config['params']['tableid']]);
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'Saved all Successfully', 'data' => $returndata, 'row' => $returndata];
    }

    public function delete($config)
    {
        $row = $config['params']['row'];
        $trno = $config['params']['tableid'];
        $this->logger->sbcmasterlog(
            $trno,
            $config,
            'DELETE COMMENT DESC' . ' - DESCRIPTION: ' . $row['comment'] . ' - LINE' . $row['line']
        );
        $qry = "delete from " . $this->table . " where trno=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$trno, $row['line']]);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }

    public function deleteallitem($config)
    {
        $trno = $config['params']['tableid'];
        $qry = "delete from " . $this->table . " where trno=?";
        $this->coreFunctions->execqry($qry, 'delete', [$trno]);
        return ['status' => true, 'msg' => 'Successfully deleted.', 'data' => []];
    }

    private function selectqry()
    {
        $qry = "line,date(createdate) as createdate, createby,  case when ispa=1 then 'true' else 'false' end as ispa";
        foreach ($this->fields as $key => $value) {
            $qry = $qry . ',' . $value;
        }
        return $qry;
    }

    private function loaddataperrecord($trno, $line)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " where trno=? and line=?
        union all
        select " . $select . " from " . $this->htable . " where trno=? and line=?
        ";
        $data = $this->coreFunctions->opentable($qry, [$trno, $line, $trno, $line]);
        return $data;
    }

    public function loaddata($config)
    {
        $trno = $config['params']['tableid'];
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " where trno=? 
        union all
        select " . $select . " from " . $this->htable . " where trno=? 
        order by line";
        $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
        return $data;
    }
} //end class
