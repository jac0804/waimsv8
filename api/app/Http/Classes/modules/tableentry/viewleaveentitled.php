<?php

namespace App\Http\Classes\modules\tableentry;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class viewleaveentitled
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;
    private $logger;

    public $modulename = 'SET RANGE';
    public $gridname = 'inventory';
    private $fields = ['line', 'first', 'last', 'days'];
    private $table = 'leaveentitled';

    public $tablelogs = 'table_log';

    public $style = 'width:50%;max-width:50%;';
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
        $attrib = array(
            'load' => 5408
        );
        return $attrib;
    }

    public function createHeadField($config)
    {
        $fields = [];
        $col1 = $this->fieldClass->create($fields);

        return array('col1' => $col1);
    }

    public function paramsdata($config)
    {
        if (isset($config['params']['row'])) {
            $trno = $config['params']['row']['line'];
        } else {
            $trno = $config['params']['dataparams']['line'];
        }

        return $this->getheaddata($trno, $config['params']['doc']);
    }

    public function getheaddata($trno, $doc)
    {
        return [];
    }

    public function data()
    {
        return [];
    }

    public function createTab($config)
    {
        $line = $config['params']['row']['line'];
        $column = ['action', 'first', 'last', 'days'];

        foreach ($column as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $column]];



        $stockbuttons = ['delete'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
        $obj[0][$this->gridname]['columns'][$first]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$last]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$days]['style'] = "width:500px;whiteSpace: normal;min-width:500px;";

        $obj[0][$this->gridname]['columns'][$days]['align'] = 'text-left';

        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $line = $config['params']['row']['line'];
        $tbuttons = ['addrecord', 'saveallentry'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }


    public function saveallentry($config)
    {
        $trno = $config['params']['sourcerow']['line'];
        $data = $config['params']['data'];
        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }

                $data2['trno'] = $trno;
                if ($data[$key]['line'] == 0) {
                    $checking = $this->coreFunctions->opentable("select first,last from " . $this->table . " where trno='" . $trno . "' and first ='" . $data[$key]['first'] . "' and last ='" . $data[$key]['last'] . "'");
                    if (!empty($checking)) return ['status' => false, 'msg' => 'Year range already exists. => ' . $data[$key]['first'] . ' - ' . $data[$key]['last']];
                    $line = $this->coreFunctions->insertGetId($this->table, $data2);
                    $this->updateCounts($trno);
                } else {
                    $checking = $this->coreFunctions->opentable("select first,last from " . $this->table . " where trno='" . $trno . "' 
                                and first ='" . $data[$key]['first'] . "' and last ='" . $data[$key]['last'] . "' and line <> '" . $data[$key]['line'] . "' 
                                ");
                    if (!empty($checking)) return ['status' => false, 'msg' => 'Year range already exists. => ' . $data[$key]['first'] . ' - ' . $data[$key]['last']];
                    $this->updateCounts($trno);
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['trno' => $data2['trno'], 'line' => $data2['line']]);
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        $sourcerow = $this->loadsourcerow($config, $trno);
        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $returndata, 'reloadtableentry' => $sourcerow];
    } // end function

    public function updateCounts($trno)
    {
        $count = $this->coreFunctions->datareader("select count(line) as value from " . $this->table . " where trno=" . $trno . "", [], '', true);
        $this->coreFunctions->execqry("update leavebatch set count=? where line=?", 'update', [$count, $trno]);
    }

    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup'];
                break;
        }
    }

    public function add($config)
    {
        $data = [];
        $data['trno'] = 0;
        $data['line'] = 0;
        $data['first'] = 0;
        $data['last'] = 0;
        $data['days'] = 0;
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    public function delete($config)
    {
        $row = $config['params']['row'];

        $this->coreFunctions->execqry("delete from " . $this->table . " where line=" . $row['line'] . " and trno=" . $row['trno'], 'delete');
        $this->updateCounts($row['trno']);
        $sourcerow = $this->loadsourcerow($config, $row['line']);
        return ['status' => true, 'msg' => 'Successfully deleted.', 'reloadtableentry' => $sourcerow];
    }

    public function loadsourcerow($config, $line)
    {
        $data = $this->coreFunctions->opentable("select line, code, codename, entitled, 
        (case when leavebatch.isnopay = 0 then 'false' else 'true' end) as isnopay, 
        count, '' as bgcolor from leavebatch order by line");
        return $data;
    }


    public function loaddataperrecord($config, $trno, $line)
    {
        $data = $this->coreFunctions->opentable("select trno, line, first, last, days, '' as bgcolor 
                        from " . $this->table . " 
                        where trno=" . $trno . " and line=" . $line);
        return $data;
    }

    public function loaddata($config)
    {
        $trno = isset($config['params']['row']['line']) ? $config['params']['row']['line'] : $config['params']['sourcerow']['line'];

        $data = $this->coreFunctions->opentable("select trno, line, first, last, days, '' as bgcolor 
            from " . $this->table . " 
            where trno=" . $trno . "");
        return $data;
    }
}
