<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\Logger;
use Exception;

class viewuominfo
{

    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;
    private $logger;


    public $modulename = 'View All Uom';
    public $gridname = 'tableentry';
    private $fields = ['uom'];
    private $table = 'uom';

    public $tablelogs = 'table_log';

    public $style = 'width:100%;max-width:70%;';
    public $showclosebtn = true;
    public $issearchshow = true;


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
        return array('col1' => $col1,);
    }
    public function paramsdata($config)
    {

        $qry = "select '" . $config['params']['row']['itemid'] . "' as itemid, concat('" . $this->modulename . "',' ~ ',i.itemname)  as modulename from item as i where i.itemid=?";

        $data = $this->coreFunctions->opentable($qry, [$config['params']['row']['itemid']]);
        $this->modulename = $data[0]->modulename;
        return $data;
    }
    public function data()
    {
        return [];
    }
    public function createTab($config)
    {

        $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'viewmmuominfo', 'label' => 'UNIT OF MESUREMENT']];
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
}
