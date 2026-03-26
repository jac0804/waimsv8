<?php

namespace App\Http\Classes\modules\hrisentry;

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
use App\Http\Classes\lookup\hris;
use App\Http\Classes\lookup\hrislookup;

class entryreturnitem
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'RETURN OF ITEMS';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'returnitemdetail';
    private $htable = 'hreturnitemdetail';
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['itemname', 'amt', 'rem', 'ref', 'refx', 'linex'];
    public $showclosebtn = false;
    private $hrislookup;
    public $tablelogs = 'hrisnum_log';
    public $tablelogs_del = 'del_hrisnum_log';



    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->hrislookup = new hrislookup;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createTab($config)
    {
        $action = 0;
        $itemname = 1;
        $amt = 2;
        $rem = 3;
        $ref = 4;

        $tab = [$this->gridname => ['gridcolumns' => ['action', 'itemname', 'amt', 'rem', 'ref']]];

        $stockbuttons = ['save', 'delete'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";

        $obj[0][$this->gridname]['columns'][$itemname]['type'] = "input";
        $obj[0][$this->gridname]['columns'][$itemname]['label'] = "Description";
        $obj[0][$this->gridname]['columns'][$itemname]['readonly'] = false;
        $obj[0][$this->gridname]['columns'][$itemname]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";

        $obj[0][$this->gridname]['columns'][$amt]['label'] = "Estimated Value";

        $obj[0][$this->gridname]['columns'][$ref]['type'] = "input";

        return $obj;
    }


    public function createtabbutton($config)
    {
        $tbuttons = ['addrecord', 'pendingturnoveritems', 'saveallentry'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function add($config)
    {
        $data = [];
        $data['trno'] = $config['params']['tableid'];
        $data['line'] = 0;
        $data['itemname'] = '';
        $data['rem'] = '';
        $data['amt'] = '0.00';
        $data['ref'] = '';
        $data['refx'] = 0;
        $data['linex'] = 0;
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    public function save($config, $row = 'row')
    {
        $data = [];
        $row = $config['params'][$row];
        $doc = $config['params']['doc'];
        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }
        $data['trno'] = $config['params']['tableid'];

        $checking = $this->coreFunctions->datareader("select count(postdate) as value 
				from hrisnum where trno = '" . $data['trno'] . "' and postdate is not null and doc = '$doc'");

        if ($checking > 0) {
            return ['status' => false, 'msg' => "Transaction Already Posted!"];
        }

        if ($row['line'] == 0) {
            $qry = "select line as value from " . $this->table . " where trno=? order by line desc limit 1";
            $line = $this->coreFunctions->datareader($qry, [$data['trno']]);
            if (!$line) {
                $line = 0;
            }
            $line = $line + 1;
            $data["line"] = $line;
            if ($this->coreFunctions->sbcinsert($this->table,  $data)) {

                if ($data["refx"] != 0 && $data["linex"] != 0) {
                    $this->setserved($data["refx"], $data["linex"], 1);
                }

                $returnrow = $this->loaddataperrecord($data['trno'], $line, $config);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {
            if ($this->coreFunctions->sbcupdate($this->table, $data, ['trno' => $data['trno'], 'line' => $row['line']]) == 1) {
                $returnrow = $this->loaddataperrecord($data['trno'], $row['line'], $config);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        }
    } //end function

    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        $trno = $config['params']['tableid'];
        $doc = $config['params']['doc'];
        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }

                $checking = $this->coreFunctions->datareader("select count(postdate) as value 
          from hrisnum where trno = '" . $trno . "' and postdate is not null and doc = '$doc'");

                if ($checking > 0) {
                    return ['status' => false, 'msg' => "Transaction Already Posted!"];
                }

                if ($data[$key]['line'] == 0) {
                    $qry = "select line as value from " . $this->table . " where trno=? order by line desc limit 1";
                    $line = $this->coreFunctions->datareader($qry, [$trno]);
                    if (!$line) {
                        $line = 0;
                    }
                    $line = $line + 1;
                    $data2["line"] = $line;
                    $data2['trno'] = $trno;
                    $insert = $this->coreFunctions->sbcinsert($this->table, $data2);
                    if ($insert) {
                        if ($data[$key]["refx"] != 0 && $data[$key]["linex"] != 0) {
                            $this->setserved($data[$key]["refx"], $data[$key]["linex"], 1);
                        }
                    }

                    $this->logger->sbcwritelog($trno, $config, 'ADD DETAILS', "DESC: " . $data[$key]['itemname'] . " LINE " . $line . "");
                } else {
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'Saved all Successfully', 'data' => $returndata];
    } // end function
    public function delete($config)
    {
        $row = $config['params']['row'];
        $trno = $config['params']['tableid'];
        $doc = $config['params']['doc'];

        $checking = $this->coreFunctions->datareader("select count(postdate) as value 
				from hrisnum where trno = '$trno' and postdate is not null and doc = '$doc'");

        if ($checking > 0) {
            return ['status' => false, 'msg' => "Transaction Already Posted!"];
        }

        $qry = "delete from " . $this->table . " where trno=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$trno, $row['line']]);

        if ($row["refx"] != 0 && $row["linex"] != 0) {
            $this->setserved($row["refx"], $row["linex"], 0);
        }

        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }

    private function selectqry()
    {
        $qry = "line";
        foreach ($this->fields as $key => $value) {
            $qry = $qry . ',' . $value;
        }
        return $qry;
    }

    private function loaddataperrecord($trno, $line, $config)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " where trno=? and line=?";
        $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
        return $data;
    }

    public function loaddata($config)
    {
        $trno = $config['params']['tableid'];
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " where trno=? 
        union all select " . $select . " from " . $this->htable . " where trno=?
        order by line";
        $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
        return $data;
    }


    public function lookupsetup($config)
    {
        return $this->hrislookup->pendingturnoveritems($config);
    }

    public function lookupcallback($config)
    {
        $id = $config['params']['tableid'];
        $row = $config['params']['rows'];
        $doc = $config['params']['doc'];
        $data = [];

        $checking = $this->coreFunctions->datareader("select count(postdate) as value 
				from hrisnum where trno = '$id' and postdate is not null and doc = '$doc'");

        if ($checking > 0) {
            return ['status' => false, 'msg' => "Transaction Already Posted!", 'data' => []];
        }

        foreach ($row  as $key2 => $value) {
            $config['params']['row']['line'] = 0;
            $config['params']['row']['trno'] = $id;
            $config['params']['row']['linex'] = $value['line'];
            $config['params']['row']['refx'] = $value['trno'];
            $config['params']['row']['ref'] = $value['docno'];
            $config['params']['row']['itemname'] = $value['itemname'];
            $config['params']['row']['amt'] = $value['amt'];
            $config['params']['row']['rem'] = $value['rem'];
            $config['params']['row']['bgcolor'] = 'bg-blue-2';
            $return = $this->save($config);
            if ($return['status']) {
                array_push($data, $return['row'][0]);
            }
        }

        return ['status' => true, 'msg' => 'Successfully added.', 'data' => $data];
    } // end function


    private function setserved($refx, $linex, $type)
    {

        if ($type == 0) {
            $qry = "update hturnoveritemdetail set qa=qa-1 where trno=? and line=?";
        } else {
            $qry = "update hturnoveritemdetail set qa=qa+1 where trno=? and line=?";
        }

        if ($this->coreFunctions->execqry($qry, 'update', [$refx, $linex]) == 1) {
        } else {
        }
    }
} //end class
