<?php

namespace App\Http\Classes\modules\autoserventry;

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

class entrycarmodel
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'CAR MODEL';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'cmodel';
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['cryear', 'model', 'crtype', 'sub_model', 'other_info'];
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

        $getcols = ['action', 'description', 'model', 'cryear', 'crtype', 'sub_model', 'other_info', 'code'];

        foreach ($getcols as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $getcols]];
        $stockbuttons = ['save', 'delete'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$description]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";
        $obj[0][$this->gridname]['columns'][$description]['label'] = "";
        $obj[0][$this->gridname]['columns'][$description]['type'] = "hidden";
        $obj[0][$this->gridname]['columns'][$model]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$cryear]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$crtype]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$cryear]['readonly'] = false;
        $obj[0][$this->gridname]['columns'][$crtype]['readonly'] = false;
        $obj[0][$this->gridname]['columns'][$sub_model]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$other_info]['style'] = "width:250px;whiteSpace: normal;min-width:250px;";
        $obj[0][$this->gridname]['columns'][$code]['label'] = "";
        $obj[0][$this->gridname]['columns'][$code]['type'] = "hidden";
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

    public function loaddata($config)
    {
        $carid = $config['params']['tableid'];
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " where carid=? 
        order by line";

        $this->coreFunctions->LogConsole($carid);
        $this->coreFunctions->LogConsole($qry);

        $data = $this->coreFunctions->opentable($qry, [$carid]);
        return $data;
    }

    private function selectqry()
    {
        $qry = "line";
        foreach ($this->fields as $key => $value) {
            $qry = $qry . ',' . $value;
        }
        return $qry;
    }

    public function add($config)
    {
        $data = [];
        $data['carid'] = $config['params']['tableid'];
        $data['line'] = 0;
        $data['cryear'] = '';
        $data['model'] = '';
        $data['crtype'] = '';
        $data['sub_model'] = '';
        $data['other_info'] = '';
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    public function save($config)
    {
        $data = [];
        $row = $config['params']['row'];
        $companyid = $config['params']['companyid'];
        $dateTables = ['cmodel'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

        foreach ($this->fields as $key => $value) {
            // $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
            $data[$value] = $this->othersClass->sanitizekeyfieldFast($value, $row[$value], $lookups);
        }
        $data['carid'] = $config['params']['tableid'];

        // if ($data['model'] == '') {
        //     $data[0]['bgcolor'] = 'bg-red-2';
        //     $data[0]['line'] = $row['line'];
        //     return ['status' => false, 'msg' => 'Invalid model', 'row' => $data];
        // }

        if ($row['line'] == 0) {
            $qry = "select line as value from " . $this->table . " where carid=? order by line desc limit 1";
            $line = $this->coreFunctions->datareader($qry, [$data['carid']]);
            if (!$line) {
                $line = 0;
            }
            $line = $line + 1;
            $data["line"] = $line;

            $data['createby']   = $config['params']['user'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();

            if ($this->coreFunctions->sbcinsert($this->table, $data)) {
                $returnrow = $this->loaddataperrecord($data['carid'], $line, $config);

                $this->logger->sbcmasterlog(
                    $data['carid'],
                    $config,
                    'CREATE CAR MODEL' . ' - MODEL: ' . $data['model'] . ' - YEAR: ' . $data['cryear'] . ' - LINE' . $line
                );
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {
            $data['editby']     = $config['params']['user'];
            $data['editdate']   = $this->othersClass->getCurrentTimeStamp();

            if ($this->coreFunctions->sbcupdate($this->table, $data, ['carid' => $data['carid'], 'line' => $row['line']]) == 1) {
                $returnrow = $this->loaddataperrecord($data['carid'], $row['line'], $config);

                $this->logger->sbcmasterlog(
                    $data['carid'],
                    $config,
                    'UPDATE CAR MODEL' . ' - MODEL: ' . $data['model'] . ' - YEAR: ' . $data['cryear'] . ' - LINE' . $row['line'],
                    1
                );
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        }
    }

    public function delete($config)
    {
        $row = $config['params']['row'];
        $carid = $config['params']['tableid'];

        $this->logger->sbcmasterlog(
            $carid,
            $config,
            'DELETE CAR MODEL' . ' - MODEL: ' . $row['model'] . ' - YEAR: ' . $row['cryear'] . ' - LINE' . $row['line']
        );

        $qry = "delete from " . $this->table . " where carid=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$carid, $row['line']]);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }

    public function deleteallitem($config)
    {
        $carid = $config['params']['tableid'];
        $qry = "delete from " . $this->table . " where carid=?";
        $this->coreFunctions->execqry($qry, 'delete', [$carid]);
        return ['status' => true, 'msg' => 'Successfully deleted.', 'data' => []];
    }

    private function loaddataperrecord($carid, $line, $config)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " where carid=? and line=?";
        $data = $this->coreFunctions->opentable($qry, [$carid, $line]);
        return $data;
    }

    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        $companyid = $config['params']['companyid'];

        $dateTables = ['cmodel'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);
        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    // $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                    $data2[$value2] = $this->othersClass->sanitizekeyfieldFast($value2, $data[$key][$value2], $lookups);
                }
                $data2['carid'] = $config['params']['tableid'];
                if ($data[$key]['line'] == 0) {
                    $data2['createby']   = $config['params']['user'];
                    $data2['createdate'] = $this->othersClass->getCurrentTimeStamp();

                    $line = $this->coreFunctions->insertGetId($this->table, $data2);

                    $qry = "select line as value from " . $this->table . " where carid=? order by line desc limit 1";
                    $checkline = $this->coreFunctions->datareader($qry, [$config['params']['tableid']]);

                    $this->logger->sbcmasterlog(
                        $config['params']['tableid'],
                        $config,
                        'INSERT CAR MODEL' . ' - MODEL: ' . $data[$key]['model'] . ' - YEAR: ' . $data[$key]['cryear'] . ' - LINE' . $checkline
                    );
                } else {
                    $data2['editby']     = $config['params']['user'];
                    $data2['editdate']   = $this->othersClass->getCurrentTimeStamp();
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['carid' => $config['params']['tableid'], 'line' => $data[$key]['line']]);

                    $this->logger->sbcmasterlog(
                        $config['params']['tableid'],
                        $config,
                        'UPDATE CAR MODEL' . ' - MODEL: ' . $data[$key]['model'] . ' - YEAR: ' . $data[$key]['cryear'] . ' - LINE' . $data[$key]['line'],
                        1
                    );
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'Saved all Successfully', 'data' => $returndata, 'row' => $returndata];
    }
}
