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

class showremhistory
{

    private $fieldClass;
    private $tabClass;
    public $modulename = 'REMARKS';
    public $gridname = 'inventory';
    public $tablenum = 'transnum';
    public $tablelogs = 'transnum_log';
    private $logger;
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    public $style = 'width:1200px;max-width:1200px;';
    public $issearchshow = true;
    public $showclosebtn = true;

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
        $attrib = array('load' => 5021, 'view' => 5021);
        return $attrib;
    }

    public function createTab($config)
    {
        $columns = ['prref', 'rem', 'createdate', 'createby'];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $columns]];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$prref]['label'] = 'Type';
        $obj[0][$this->gridname]['columns'][$rem]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$createby]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$createdate]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$prref]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$rem]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$createdate]['style'] = "width:90px;whiteSpace: normal;min-width:90px;";
        $obj[0][$this->gridname]['columns'][$createby]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
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
        $viewaccess = $this->othersClass->checkAccess($config['params']['user'], 5021);
        $trno = isset($config['params']['row']['trno']) ? $config['params']['row']['trno'] : 0;
        $line = isset($config['params']['row']['line']) ? $config['params']['row']['line'] : 0;

        $qry = "select prref,rem,createdate,createby from headprrem where trno= $trno order by createdate desc";
        return $this->coreFunctions->opentable($qry);
    }
}
