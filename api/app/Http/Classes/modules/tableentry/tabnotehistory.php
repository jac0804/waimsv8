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
use App\Http\Classes\lookup\constructionlookup;

class  tabnotehistory
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'NOTE HISTORY';
    public $tablenum = 'transnum';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'detailrems';
    private $othersClass;
    public $style = 'width:100%;max-width: 100%';
    private $fields = [];
    public $showclosebtn = true;
    public $tablelogs = 'table_log';
    
    public $logger;
    public $sqlquery;
    public $constructionlookup;

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
    }

    public function getAttrib()
    {
        $attrib = ['load' => 0];
        return $attrib;
    }

    public function createTab($config)
    {
        $doc = $config['params']['doc'];

        $rem = 0;
        $createby = 1;
        $createdate = 2;

        $tab = [$this->gridname => ['gridcolumns' => ['rem', 'createby', 'createdate']]];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$rem]['style'] = "width:650px;whiteSpace: normal;min-width:650px;";
        $obj[0][$this->gridname]['columns'][$rem]['type'] = 'textarea';
        $obj[0][$this->gridname]['columns'][$createby]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$createby]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$createdate]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$createdate]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";

        $obj[0][$this->gridname]['columns'][$rem]['readonly'] = true;
        

        $obj[0][$this->gridname]['columns'][$createby]['label'] = 'Create By';


        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
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
        $doc = $config['params']['doc'];
        $trno = $config['params']['tableid'];

        switch ($doc) {
            case 'CV':
                $qry = 'select rem, createby,createdate from detailrems where trno=?
                    order by createdate desc';
                $data = $this->coreFunctions->opentable($qry, [$trno]);
                break;
                
        }

        return $data;
    }
}
