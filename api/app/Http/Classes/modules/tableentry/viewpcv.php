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

class viewpcv
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'PCV List';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'hsvhead';
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['docno'];
    public $issearchshow = true;
    public $showclosebtn = false;


    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->enrollmentlookup = new enrollmentlookup;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createTab($config)
    {
        $tab = [$this->gridname => ['gridcolumns' => ['action', 'docno']]];

        $stockbuttons = ['delete'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][0]['style'] = "width:30px;whiteSpace: normal;min-width:30px;";
        $obj[1][$this->gridname]['columns'][1]['readonly'] = true;
        $obj[1][$this->gridname]['columns'][1]['type'] = 'label';

        return $obj;
    }

    public function createHeadField($config)
    {
        return [];
    }

    public function paramsdata($config)
    {
        return [];
    }

    public function data()
    {
        return [];
    }

    public function createtabbutton($config)
    {
        return 0;
    }


    private function selectqry()
    {
        $qry = "";
        return $qry;
    }

    private function loaddataperrecord($trno)
    {
        $qry = "select docno from hsvhead where cvtrno=? ";
        $data = $this->coreFunctions->opentable($qry, [$trno]);
        return $data;
    }

    public function loaddata($config)
    {
        $trno = $config['params']['tableid'];
        $qry = "select trno, docno, cvtrno from hsvhead where cvtrno=? ";
        $data = $this->coreFunctions->opentable($qry, [$trno]);
        return $data;
    }

    public function delete($config)
    {
        $row = $config['params']['row'];
        $update = "update hsvhead set cvtrno =0 where trno =? and cvtrno =?";
        $this->coreFunctions->execqry($update, 'update', [$row['trno'], $row['cvtrno']]);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }
} //end class
