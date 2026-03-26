<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class viewitembaseprice
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;

    public $modulename = 'Base Price';
    public $gridname = 'tableentry';
    private $fields = [
        'amt16', 'disc16', 'disc17', 'disc18', 'disc19', 'disc20', 'disc21', 'disc22'
    ];
    private $table = 'item';

    public $tablelogs = 'item_log';
    public $tablelogs_del = 'del_item_log';

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
        $attrib = array('load' => 12, 'save' => 15);
        return $attrib;
    }

    public function createHeadField($config)
    {
        if (isset($config['params']['clientid'])) {
            if ($config['params']['clientid'] != 0) {
                $itemid = $config['params']['clientid'];
                $item = $this->othersClass->getitemname($itemid);
                $this->modulename = 'ITEM PRICE - ' . $item[0]->barcode . ' - - - ' . $item[0]->itemname;
            } else {
                return [];
            }
        } else {
            return [];
        }

        $fields = ['amt16'];
        $col1 = $this->fieldClass->create($fields);

        $fields = ['disc16', 'disc17', 'disc18', 'disc19', 'disc20'];
        $col2 = $this->fieldClass->create($fields);

        $fields = ['disc21', 'disc22'];
        $col3 = $this->fieldClass->create($fields);

        $fields = ['refresh'];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'refresh.label', 'Save');
        data_set($col4, 'refresh.isclose', true);
        return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
    }

    private function selectqry()
    {
        $qry = "itemid";
        foreach ($this->fields as $key => $value) {
            $qry = $qry . ',' . $value;
        }
        return $qry;
    }

    public function paramsdata($config)
    {
        $itemid = $config['params']['clientid'];
        $qry = $this->selectqry();
        $qry = "select " . $qry . " from " . $this->table . " where itemid = ?";
        $data = $this->coreFunctions->opentable($qry, [$itemid]);
        return $data;
    }

    public function data()
    {
        return [];
    }

    public function createTab($config)
    {
        $tab = [];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
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
        $data = [];
        $itemid = $config['params']['itemid'];

        $row = $config['params']['dataparams'];
        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['dlock'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        $this->coreFunctions->sbcupdate($this->table, $data, ['itemid' => $itemid]);

        $config['params']['clientid'] = $config['params']['itemid'];
        $txtdata = $this->paramsdata($config);
        return ['status' => true, 'msg' => 'Save Item Price Success', 'data' => [], 'txtdata' => $txtdata];
    }
}
