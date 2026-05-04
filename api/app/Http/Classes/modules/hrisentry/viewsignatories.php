<?php

namespace App\Http\Classes\modules\hrisentry;

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
use App\Http\Classes\lookup\hrislookup;

class viewsignatories
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Signatories';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'hrissig';
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = [];
    public $showclosebtn = true;


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
        $columns = ['clientname', 'donedate', 'rem'];
        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $columns]];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Employee Name';
        $obj[0][$this->gridname]['columns'][$donedate]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$donedate]['label'] = 'Clear Date';
        $obj[0][$this->gridname]['columns'][$rem]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$rem]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$rem]['label'] = 'Remarks';
        $obj[0][$this->gridname]['columns'][$rem]['style'] = 'width: 500px;whiteSpace: normal;min-width:500px;max-width:500px;';
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
        $qry = "select client.clientname, sig.donedate, sig.rem from hrissig as sig left join client on client.clientid=sig.clientid where sig.trno=? order by sig.donedate";
        $data = $this->coreFunctions->opentable($qry, [$tableid]);
        return $data;
    }
} //end class
