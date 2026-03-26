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
use App\Http\Classes\lookup\enrollmentlookup;

class entryjobdesc
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'JOB DESCRIPTION';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'jobtdesc';
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['description'];
    public $tablelogs = 'masterfile_log';
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

        $getcols = ['action', 'description'];

        foreach ($getcols as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $getcols]];
        $stockbuttons = ['save', 'delete'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
        $obj[0][$this->gridname]['columns'][$description]['style'] = "width:900px;whiteSpace: normal;min-width:900px;";

        return $obj;
    }


    public function createtabbutton($config)
    {
        $tbuttons = ['addrecord', 'saveallentry', 'deleteallitem'];
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
        $data['description'] = '';
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

        if ($data['description'] == '') {
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
            if ($this->coreFunctions->sbcinsert($this->table,  $data)) {
                $returnrow = $this->loaddataperrecord($data['trno'], $line, $config);

                $this->logger->sbcmasterlog(
                    $data['trno'],
                    $config,
                    'CREATE JOB DESC' . ' - DESCRIPTION: ' . $data['description'] . ' - LINE' . $line
                );
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {
            if ($this->coreFunctions->sbcupdate($this->table, $data, ['trno' => $data['trno'], 'line' => $row['line']]) == 1) {
                $returnrow = $this->loaddataperrecord($data['trno'], $row['line'], $config);

                $this->logger->sbcmasterlog(
                    $data['trno'],
                    $config,
                    'UPDATE JOB DESC' . ' - DESCRIPTION: ' . $data['description'] . ' - LINE' . $row['line'],
                    1
                );
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
                $data2['trno'] = $config['params']['tableid'];
                if ($data[$key]['line'] == 0) {
                    $line = $this->coreFunctions->insertGetId($this->table, $data2);


                    $qry = "select line as value from " . $this->table . " where trno=? order by line desc limit 1";
                    $checkline = $this->coreFunctions->datareader($qry, [$config['params']['tableid']]);

                    $this->logger->sbcmasterlog(
                        $config['params']['tableid'],
                        $config,
                        'INSERT JOB DESC' . ' - DESCRIPTION: ' . $data[$key]['description'] . ' - LINE' . $checkline
                    );
                } else {
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);

                    $this->logger->sbcmasterlog(
                        $config['params']['tableid'],
                        $config,
                        'UPDATE JOB DESC' . ' - DESCRIPTION: ' . $data[$key]['description'] . ' - LINE' . $data[$key]['line'],
                        1
                    );
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
            'DELETE JOB DESC' . ' - DESCRIPTION: ' . $row['description'] . ' - LINE' . $row['line']
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
        $qry = "line";
        foreach ($this->fields as $key => $value) {
            $qry = $qry . ',' . $value;
        }
        return $qry;
    }

    private function loaddataperrecord($trno, $line)
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
        order by line";

        $this->coreFunctions->LogConsole($trno);
        $this->coreFunctions->LogConsole($qry);

        $data = $this->coreFunctions->opentable($qry, [$trno]);
        return $data;
    }
} //end class
