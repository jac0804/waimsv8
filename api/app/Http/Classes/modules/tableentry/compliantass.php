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

class compliantass
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;
    private $logger;

    public $modulename = 'COMPLIANTS/ASSESSMENT';
    public $gridname = 'inventory';
    private $fields = ['trno'];
    public $tablenum = 'transnum';
    private $table = 'jostock';
    private $htable = 'hjostock';
    private $stock = 'stockinfotrans';
    private $hstock = 'hstockinfotrans';

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
        $itemdesc = 1;

        $stockbuttons = ['save', 'delete'];
        $columns = ['action', 'itemdesc'];
        $tab = [
            $this->gridname => [
                'gridcolumns' => $columns
            ]
        ];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$action]['style'] = 'width:10%;whiteSpace: normal;min-width:100%;';
        $obj[0][$this->gridname]['columns'][$itemdesc]['label'] = 'Description';
        $obj[0][$this->gridname]['columns'][$itemdesc]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$itemdesc]['readonly'] = false;


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
        $data['itemdesc'] = '';
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }
    public function delete($config)
    {
        $config['params']['trno'] = $config['params']['tableid'];
        $isposted = $this->othersClass->isposted($config);
        $usetable = $this->table;
        $stocktable = $this->stock;
        if ($isposted == true) {
            $usetable = $this->htable;
            $stocktable = $this->hstock;
        }
        $row = $config['params']['row'];
        $qry = "delete from " . $usetable . " where line=? and trno = ?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line'], $row['trno']]);
        $qry2 = "delete from " . $stocktable . " where line=? and trno = ?";
        $this->coreFunctions->execqry($qry2, 'delete', [$row['line'], $row['trno']]);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }
    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        $config['params']['trno'] = $config['params']['tableid'];
        $data2 = [];
        foreach ($data as $key => $value) {
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }
                if ($data[$key]['itemdesc'] == '') {
                    return ['status' => false, 'msg' => 'Description is empty', 'data' => []];
                }
                $itemdesc = $data[$key]['itemdesc'];
                if ($data[$key]['line'] == 0) {
                    $data2['trno'] = $config['params']['trno'];
                    $line = $this->coreFunctions->datareader("select ifnull(count(line),0)+1 as value from jostock where trno=?", [$config['params']['trno']]);
                    $data2['line'] = $line;
                    $data2['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['encodedby'] = $config['params']['user'];
                    $insert = $this->coreFunctions->sbcinsert($this->table, $data2);
                    $this->logger->sbcwritelog($config['params']['trno'], $config, 'Item Description', 'CREATE - Descriptio: ' . $data[$key]['itemdesc']);
                    if ($insert == 1) {
                        $stockinfo_data = [
                            'trno' => $config['params']['trno'],
                            'line' => $line,
                            'itemdesc' => $itemdesc
                        ];
                        $this->coreFunctions->sbcinsert('stockinfotrans', $stockinfo_data);
                    }
                } else {
                    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['editby'] = $config['params']['user'];
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line'], 'trno' => $data[$key]['trno']]);
                    $stockinfo_data = [
                        'editdate' => $this->othersClass->getCurrentTimeStamp(),
                        'editby' => $config['params']['user'],
                        'itemdesc' => $itemdesc
                    ];
                    $this->coreFunctions->sbcupdate('stockinfotrans', $stockinfo_data, ['line' => $data[$key]['line'], 'trno' => $data[$key]['trno']]);
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
        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }
        if ($row['line'] == 0) {
            $data['trno'] = $config['params']['trno'];
            $line = $this->coreFunctions->datareader("select ifnull(count(line),0)+1 as value from jostock where trno=?", [$config['params']['trno']]);
            $data['line'] = $line;
            $data2['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
            $data2['encodedby'] = $config['params']['user'];
            $insert = $this->coreFunctions->sbcinsert($this->table, $data);
            if ($insert == 1) {
                $stockinfo_data = [
                    'trno' => $config['params']['trno'],
                    'line' => $line,
                    'itemdesc' => $row['itemdesc']
                ];
                $this->coreFunctions->sbcinsert('stockinfotrans', $stockinfo_data);
            }
            $returnrow = $this->loaddataperrecord($config, $line);
            $this->logger->sbcwritelog($config['params']['trno'], $config, 'Item Description', 'CREATE - Description: ' . $row['itemdesc']);
            return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
        } else {
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            $this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line'], 'trno' => $row['trno']]);
            $stockinfo_data = [
                'editdate' => $this->othersClass->getCurrentTimeStamp(),
                'editby' => $config['params']['user'],
                'itemdesc' => $row['itemdesc']
            ];
            $this->coreFunctions->sbcupdate('stockinfotrans', $stockinfo_data, ['line' => $row['line'], 'trno' => $row['trno']]);
            $returnrow = $this->loaddataperrecord($config, $row['line']);
            return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
        }
    } //end function
    private function selectqry()
    {
        $qry = 'stock.line , stock.trno, info.itemdesc';
        return $qry;
    }
    private function loaddataperrecord($config, $line)
    {
        $trno =  $config['params']['tableid'];
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . "  as stock
        left join stockinfotrans as info on info.trno = stock.trno and info.line = stock.line  
        where stock.line = ? and stock.trno = ?
        union all 
        select " . $select . " from " . $this->htable . "  as stock 
        left join hstockinfotrans as info on info.trno = stock.trno and info.line = stock.line
         where  stock.line = ? and stock.trno = ? ";
        $data = $this->coreFunctions->opentable($qry, [$line, $trno, $line, $trno]);
        return $data;
    }
    public function loaddata($config)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";

        $trno = $config['params']['tableid'];
        $qry = "select " . $select . " from " . $this->table . "  as stock
        left join stockinfotrans as info on info.trno = stock.trno and info.line = stock.line  
        where stock.trno = ?
        union all 
        select " . $select . " from " . $this->htable . "  as stock 
        left join hstockinfotrans as info on info.trno = stock.trno and info.line = stock.line
         where stock.trno = ? order by line desc";
        $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
        return $data;
    }
}
