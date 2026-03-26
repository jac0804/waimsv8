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

class  tabupdatestockdetails
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'STOCK INFO';
    public $tablenum = 'transnum';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'postock';
    private $htable = 'hpostock';
    private $othersClass;
    public $style = 'width:100%;max-width: 100%';
    private $fields = ['trno', 'line', 'isadv'];
    private $Stocksfields = ['trno', 'line', 'status', 'suppid'];
    public $showclosebtn = true;
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';
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
        $attrib = ['load' => 0];
        return $attrib;
    }

    public function createTab($config)
    {
        $itemname = 0;
        $itemdesc = 1;
        $specs = 2;
        $isadv = 3;


        $tab = [$this->gridname => ['gridcolumns' => ['itemname', 'itemdesc', 'specs', 'isadv']]];

        $stockbuttons = [];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$itemname]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;';
        $obj[0][$this->gridname]['columns'][$itemdesc]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;';
        $obj[0][$this->gridname]['columns'][$specs]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $obj[0][$this->gridname]['columns'][$isadv]['style'] = 'width:120px;whiteSpace: normal;min-width:200px;';

        $obj[0][$this->gridname]['columns'][$isadv]['readonly'] = false;

        $obj[0][$this->gridname]['columns'][$itemname]['label'] = 'Item Name (Stockcard)';
        $obj[0][$this->gridname]['columns'][$itemdesc]['label'] = 'Item Name (Requestor)';

        return $obj;
    }

    public function createtabbutton($config)
    {
        $trno = $config['params']['tableid'];
        $isposted = $this->othersClass->isposted2($trno, 'transnum');
        if ($isposted) {
            $tbuttons = ['saveallentry'];
        } else {
            $tbuttons = [];
        }

        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }


    public function lookupsetup($config)
    {
    }

    public function lookupcallback($config)
    {
    }

    public function lookupduration($config)
    {
    } // end function

    public function lookupitemstat($config)
    {
    } // end function

    public function lookupemployeepo($config)
    {
    } // end function

    public function lookupisasset($config)
    {
    } // end function

    public function saveallentry($config)
    {
        $trno = $config['params']['tableid'];
        $data = $config['params']['data'];
        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }

                $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                $data2['editby'] = $config['params']['user'];
                if ($data[$key]['line'] != 0) {
                    $this->coreFunctions->sbcupdate($this->htable, $data2, ['trno' => $data[$key]['trno'], 'line' => $data[$key]['line']]);
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata, 'reloadhead' => true];
    } // end function $$


    private function selectqry($config)
    {
        return " select stock.trno,stock.line,item.itemname,info.itemdesc,info.itemdesc2,case when stock.isadv=0 then 'false' else 'true' end as isadv,info.specs ";
    }

    public function loaddata($config)
    {

        $trno = $config['params']['tableid'];

        $sqlselect = $this->selectqry($config);
        $qry = $sqlselect . ",'' as bgcolor from hpostock as stock
                left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
                left join item on item.itemid=stock.itemid
                where stock.trno  = ? 
                order by stock.line";
        $data = $this->coreFunctions->opentable($qry, [$trno]);
        return $data;
    }
} //end class
