<?php

namespace App\Http\Classes\modules\tableentry;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

use Datetime;

class sbcremarks
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;
    private $logger;

    public $modulename = 'SBC REMARKS';
    public $gridname = 'inventory';
    private $fields = ['trno', 'station', 'serial', 'remarks', 'others'];
    public $tablenum = 'cntnum';
    private $table = 'particulars';
    private $htable = 'hparticulars';

    public $tablelogs = 'table_log';

    public $style = 'width:100%;max-width:70%;';
    public $issearchshow = true;
    public $showclosebtn = true;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->coreFunctions = new coreFunctions;
        $this->companysetup = new companysetup;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createHeadField($config)
    {
        $fields = [];
        $col1 = $this->fieldClass->create($fields);
        return array('col1' => $col1);
    }



    public function createTab($config)
    {

        $action = 0;
        $station = 1;
        $serial = 2;
        $remarks = 3;
        $others = 4;
        $stockbuttons = ['save', 'delete'];
        $columns = ['action', 'station', 'serial', 'remarks', 'others'];
        $tab = [
            $this->gridname => [
                'gridcolumns' => $columns
            ]
        ];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$action]['style'] = 'width:10%;whiteSpace: normal;min-width:100%;';
        $obj[0][$this->gridname]['columns'][$station]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $obj[0][$this->gridname]['columns'][$serial]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $obj[0][$this->gridname]['columns'][$remarks]['style'] = 'width:600px;whiteSpace: normal;min-width:600px;';
        $obj[0][$this->gridname]['columns'][$others]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
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
        $data['trno'] = 0;
        $data['line'] = 0;
        $data['station'] = '';
        $data['serial'] = '';
        $data['remarks'] = '';
        $data['others'] = '';
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }
    public function delete($config)
    {
        $config['params']['trno'] = $config['params']['tableid'];
        $isposted = $this->othersClass->isposted($config);
        $usetable = $this->table;
        if ($isposted == true) {
            $usetable = $this->htable;
        }
        $row = $config['params']['row'];
        $qry = "delete from " . $usetable . " where line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }
    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        $config['params']['trno'] = $config['params']['tableid'];
        $data2 = [];
        $isposted = $this->othersClass->isposted($config);
        $usetable = $this->table;
        if ($isposted) {
            $usetable = $this->htable;
        }
        foreach ($data as $key => $value) {
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }
                if ($data[$key]['line'] == 0) {
                    $data2['trno'] = $config['params']['trno'];
                    $line = $this->coreFunctions->datareader("select ifnull(count(line),0)+1 as value from $usetable where trno=?", [$config['params']['trno']]);
                    $data2['line'] = $line;
                    $data2['createby'] = $config['params']['user'];
                    $data2['createdate'] = $this->othersClass->getCurrentTimeStamp();

                    $this->coreFunctions->sbcinsert($usetable, $data2);
                    $this->logger->sbcwritelog($config['params']['trno'], $config, 'PARTICULARS', 'CREATE - Station: ' . $data[$key]['station'] . '- Serial: ' . $data[$key]['remarks'] . '- Remarks: ' . $data[$key]['remarks'] . '- Others: ' . $data[$key]['others']);
                } else {
                    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['editby'] = $config['params']['user'];
                    $this->coreFunctions->sbcupdate($usetable, $data2, ['line' => $data[$key]['line'], 'trno' => $data[$key]['trno']]);
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
    } // end function  
    public function save($config)
    {
        $data = [];
        $row = $config['params']['row'];
        $config['params']['trno'] = $config['params']['tableid'];
        $isposted = $this->othersClass->isposted($config);
        $usetable = $this->table;
        if ($isposted) {
            $usetable = $this->htable;
        }
        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }
        if ($row['line'] == 0) {
            $data['trno'] = $config['params']['trno'];
            $line = $this->coreFunctions->datareader("select ifnull(count(line),0)+1 as value from $usetable where trno=?", [$config['params']['trno']]);
            $data['line'] = $line;
            $data['createby'] = $config['params']['user'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();

            $this->coreFunctions->sbcinsert($usetable, $data);
            $returnrow = $this->loaddataperrecord($config, $line);
            $this->logger->sbcwritelog($config['params']['trno'], $config, 'PARTICULARS', 'CREATE - Station: ' . $row['station'] . '- Serial: ' . $row['serial'] . '- Remarks: ' . $row['remarks'] . '- Others: ' . $row['others']);
            return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
        } else {
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            $this->coreFunctions->sbcupdate($usetable, $data, ['line' => $row['line'], 'trno' => $row['trno']]);
            $returnrow = $this->loaddataperrecord($config, $row['line']);
            return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
        }
    } //end function
    private function selectqry()
    {
        $qry = "line";
        foreach ($this->fields as $key => $value) {
            $qry = $qry . ',' . $value;
        }
        return $qry;
    }
    private function loaddataperrecord($config, $line)
    {
        $trno =  $config['params']['tableid'];
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . "  where line = ? and trno = ?
        union all 
        select " . $select . " from " . $this->htable . "  where line = ? and trno = ?";
        $data = $this->coreFunctions->opentable($qry, [$line, $trno, $line, $trno]);
        return $data;
    }
    public function loaddata($config)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";

        $trno = $config['params']['tableid'];
        $qry = "select " . $select . " from " . $this->table . "  where trno = ?
        union all 
        select " . $select . " from " . $this->htable . "  where trno = ?";
        $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
        return $data;
    }
}
