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
use App\Http\Classes\lookup\enrollmentlookup;
use Exception;

class viewmmuominfo
{

    private $fieldClass;
    private $tabClass;
    private $companysetup;
    private $coreFunctions;

    public $modulename = 'Uom List';
    public $gridname = 'inventory';
    private $table = 'uom';
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['uom'];
    public $issearchshow = true;
    public $showclosebtn = false;
    public $showsearch = false;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
    }
    public function getAttrib()
    {
        $attrib = array('load' => 0);
        return $attrib;
    }
    public function createTab($config)
    {
        $action = 0;
        $uom = 1;
        $factor = 2;
        $unit = 3;
        $columns = ['action', 'uom', 'factor', 'unit'];
        $stockbuttons = ['save'];
        $tab = [$this->gridname => ['gridcolumns' => $columns]];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$action]['btns']['save']['icon'] =  'update';
        $obj[0][$this->gridname]['columns'][$action]['btns']['save']['label'] =  'Save New UOM';
        $obj[0][$this->gridname]['columns'][$action]['btns']['save']['confirm'] =  true;
        $obj[0][$this->gridname]['columns'][$action]['btns']['save']['confirmlabel'] =  'Are you sure you want to save the NEW UOM?';

        $obj[0][$this->gridname]['columns'][$uom]['type'] =  'label';
        $obj[0][$this->gridname]['columns'][$factor]['type'] =  'label';
        $obj[0][$this->gridname]['columns'][$unit]['label'] =  'New Uom';
        $obj[0][$this->gridname]['columns'][$unit]['readonly'] =  false;
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
        $itemid  = 0;
        if (isset($config['params']['ledgerdata'])) {
            $itemid = $config['params']['ledgerdata']['itemid'];
            $qry = "select itemid,uom,factor, '' as unit from " . $this->table . " where itemid = ? ";
            $data = $this->coreFunctions->opentable($qry, [$itemid]);
        } else {
            $data = [];
        }
        return $data;
    }
    public function save($config)
    {
        $row = $config['params']['row'];

        if ($row['unit'] == '') {
            return ['status' => false, 'msg' => 'Please input valid UOM'];
        }

        $exist = $this->coreFunctions->getfieldvalue("uom", "itemid", "itemid=? and uom=?", [$row['itemid'], $row['unit']], '', true);
        if ($exist != 0) {
            return ['status' => false, 'msg' => 'New UOM ' . $row['uom'] . ' already exists.'];
        }

        try {
            $data = ['uom' => $row['unit'], 'editby' => $config['params']['user'], 'editdate' => $this->othersClass->getCurrentTimeStamp()];

            $this->coreFunctions->sbcupdate("prstock", $data, ['itemid' => $row['itemid'], 'uom' => $row['uom']]);
            $this->coreFunctions->sbcupdate("hprstock", $data, ['itemid' => $row['itemid'], 'uom' => $row['uom']]);
            $this->coreFunctions->sbcupdate("cdstock", $data, ['itemid' => $row['itemid'], 'uom' => $row['uom']]);
            $this->coreFunctions->sbcupdate("hcdstock", $data, ['itemid' => $row['itemid'], 'uom' => $row['uom']]);
            $this->coreFunctions->sbcupdate("postock", $data, ['itemid' => $row['itemid'], 'uom' => $row['uom']]);
            $this->coreFunctions->sbcupdate("hpostock", $data, ['itemid' => $row['itemid'], 'uom' => $row['uom']]);
            $this->coreFunctions->sbcupdate("lastock", $data, ['itemid' => $row['itemid'], 'uom' => $row['uom']]);
            $this->coreFunctions->sbcupdate("glstock", $data, ['itemid' => $row['itemid'], 'uom' => $row['uom']]);
            $this->coreFunctions->sbcupdate("oqstock", $data, ['itemid' => $row['itemid'], 'uom' => $row['uom']]);
            $this->coreFunctions->sbcupdate("hoqstock", $data, ['itemid' => $row['itemid'], 'uom' => $row['uom']]);
            $this->coreFunctions->sbcupdate("omstock", $data, ['itemid' => $row['itemid'], 'uom' => $row['uom']]);
            $this->coreFunctions->sbcupdate("uom", $data, ['itemid' => $row['itemid'], 'uom' => $row['uom']]);
            $this->coreFunctions->sbcupdate("item", $data, ['itemid' => $row['itemid'], 'uom' => $row['uom']]);

            $this->coreFunctions->sbcupdate("rrstatus", ['uom' => $row['unit']], ['itemid' => $row['itemid'], 'uom' => $row['uom']]);

            $returnrow = $this->loaddataperrecord($row['itemid'], $row['unit']);

            return ['status' => true, 'msg' => 'UOM successfully changed.', 'row' => $returnrow];
        } catch (Exception $e) {
            return ['status' => false, 'msg' => 'Failed to up. ' . $e];
        }
    }

    public function loaddataperrecord($itemid, $uom)
    {
        $qry = "select itemid,uom,factor, '' as unit from " . $this->table . " where itemid = ? and uom = ?";
        return $this->coreFunctions->opentable($qry, [$itemid, $uom]);
    }
}
