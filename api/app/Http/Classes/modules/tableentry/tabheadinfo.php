<?php

namespace App\Http\Classes\modules\tableentry;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use Datetime;
use DateInterval;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\lookup\constructionlookup;
use App\Http\Classes\lookup\warehousinglookup;

class  tabheadinfo
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'HEAD INFO';
    public $tablenum = 'cntnum';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'cntnuminfo';
    private $htable = 'hcntnuminfo';
    private $othersClass;
    public $style = 'width:100%;max-width: 100%';
    private $fields = ['mktg', 'dc', 'bo', 'card', 'openingintro', 'e2e', 'rebate', 'rtv'];
    private $Stocksfields = [];
    public $showclosebtn = true;
    public $tablelogs = 'table_log';
    public $tablelogs_del = 'del_table_log';
    private $constructionlookup;
    private $sqlquery;
    private $logger;
    private $warehousinglookup;


    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->constructionlookup = new constructionlookup;
        $this->sqlquery = new sqlquery;
        $this->logger = new Logger;
        $this->warehousinglookup = new warehousinglookup;
    }

    public function getAttrib()
    {
        $attrib = [
            'load' => 0
        ];
        return $attrib;
    }

    public function createTab($config)
    {
        $action = 0;
        $mktg = 0;
        $dc = 1;
        $bo = 2;
        $card = 3;
        $openingintro = 4;
        $e2e = 5;
        $rebate = 6;
        $rtv = 7;


        $tab = [$this->gridname => ['gridcolumns' => ['action', 'mktg', 'dc', 'bo', 'card', 'openingintro', 'e2e', 'rebate', 'rtv']]];

        $stockbuttons = ['save', 'delete'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$action]['style'] = 'width:50px;whiteSpace: normal;min-width:50px;';
        $obj[0][$this->gridname]['columns'][$mktg]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $obj[0][$this->gridname]['columns'][$dc]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $obj[0][$this->gridname]['columns'][$bo]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $obj[0][$this->gridname]['columns'][$card]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $obj[0][$this->gridname]['columns'][$openingintro]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $obj[0][$this->gridname]['columns'][$e2e]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $obj[0][$this->gridname]['columns'][$rebate]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $obj[0][$this->gridname]['columns'][$rtv]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        return $obj;
    }

    public function createtabbutton($config)
    {
        $trno = $config['params']['tableid'];

        $tbuttons = ['addrecord', 'saveallentry'];

        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }

    public function add($config)
    {
        $data = [];
        $data['trno'] = 0;
        $data['mktg'] = 0;
        $data['dc'] = 0;
        $data['bo'] = 0;
        $data['card'] = 0;
        $data['openingintro'] = 0;
        $data['e2e'] = 0;
        $data['rebate'] = 0;
        $data['rtv'] = 0;
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }


    public function delete($config)
    {
        $config['params']['trno'] = $config['params']['tableid'];

        $row = $config['params']['row'];
        $qry = "delete from " . $this->table . " where trno=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['trno']]);
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

                if ($data[$key]['trno'] == 0) {
                    $data2['trno'] = $config['params']['trno'];
                    $this->coreFunctions->sbcinsert($this->table, $data2);
                } else {
                    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['editby'] = $config['params']['user'];
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['trno' => $data[$key]['trno']]);
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
    } // end function  

    private function selectqry()
    {
        $qry = "trno";
        foreach ($this->fields as $key => $value) {
            $qry = $qry . ',' . $value;
        }
        return $qry;
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
} //end class
