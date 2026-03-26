<?php

namespace App\Http\Classes\modules\tableentry;

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
use App\Http\Classes\SBCPDF;

class itemlist
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Item List';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $logger;
    private $table = 'item';
    private $othersClass;
    public $style = 'width:100%';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    public $showclosebtn = false;
    private $reporter;


    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->reporter = new SBCPDF;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 5018
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $columns = ['barcode', 'itemname'];
        foreach ($columns as $key => $value) {
            $$value = $key;
        }
        $tab = [
            $this->gridname => [
                'gridcolumns' => $columns
            ]
        ];

        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$barcode]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$itemname]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";

        $obj[0][$this->gridname]['columns'][$barcode]['label'] = "Item Barcode";
        $obj[0][$this->gridname]['columns'][$barcode]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$itemname]['type'] = "label";
        return $obj;
    }


    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function loaddata($config)
    {
        $tableid = $config['params']['tableid'];
        $qry = "select  itemid,barcode,itemname  from " . $this->table . " 
        left join client as cl on cl.clientid=item.supplier where cl.clientid=$tableid";
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }
} //end class