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

class  rrtabstock
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'STOCK INFO';
    public $tablenum = 'cntnum';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'serialin';
    private $othersClass;
    public $style = 'width:100%;max-width: 100%';
    private $fields = ['trno', 'line', 'serial', 'chassis', 'color', 'pnp', 'csr', 'dateid', 'sline'];
    public $showclosebtn = false;
    public $tablelogs = 'table_log';
    public $tablelogs_del = 'del_table_log';
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
        $attrib = [
            'load' => 0
        ];
        return $attrib;
    }

    public function createTab($config)
    {
        $itemname = 0;
        $serial = 1;
        $chassis = 2;
        $color = 3;
        $pnp = 4;
        $csr = 5;
        $tab = [$this->gridname => ['gridcolumns' => ['itemname', 'serial', 'chassis', 'color', 'pnp', 'csr', 'dateid']]];
        $stockbuttons = ['save'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$itemname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $obj[0][$this->gridname]['columns'][$serial]['style'] = 'width:120px;whiteSpace: normal;min-width:200px;';
        $obj[0][$this->gridname]['columns'][$chassis]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $obj[0][$this->gridname]['columns'][$color]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $obj[0][$this->gridname]['columns'][$pnp]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $obj[0][$this->gridname]['columns'][$csr]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $obj[0][$this->gridname]['columns'][$itemname]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$itemname]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$itemname]['label'] = 'Itemname';


        $obj[0][$this->gridname]['columns'][$serial]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$chassis]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$color]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$color]['readonly'] = true;

        $obj[0][$this->gridname]['columns'][$pnp]['readonly'] = false;
        $obj[0][$this->gridname]['columns'][$csr]['readonly'] = false;

        $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $trno = $config['params']['tableid'];
        $isposted = $this->othersClass->isposted2($trno, 'cntnum');
        if ($isposted) {
            $tbuttons = ['saveallentry'];
        } else {
            $tbuttons = [];
        }
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }


    public function saveallentry($config)
    {

        $data = $config['params']['data'];
        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }

                $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                $data2['editby'] = $config['params']['user'];
                $data2['csr']  = $data[$key]['csr'];
                $data2['pnp']  = $data[$key]['pnp'];
                $data2['dateid']  = $data[$key]['dateid'];


                if ($data[$key]['sline'] != 0) {
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['trno' => $data[$key]['trno'], 'sline' => $data[$key]['sline']]);
                }
            } // end if
        } // foreach

        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata, 'reloadhead' => true];
    } // end function


    public function loaddata($config)
    {
        $trno = $config['params']['tableid'];

        $qry = " select ss.trno,ss.line,i.itemname,ss.serial,ss.chassis,ss.color,ss.pnp,ss.csr,ss.sline,ss.dateid,'' as bgcolor from " . $this->table . " as ss
                left join glstock as rr on rr.trno = ss.trno and rr.line = ss.line
                left join item as i on i.itemid=rr.itemid
                where ss.trno = ?
                order by ss.line";

        $data = $this->coreFunctions->opentable($qry, [$trno]);
        return $data;
    }
} //end class
