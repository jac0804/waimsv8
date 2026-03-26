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

class entryagentmembers
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'MEMBERS';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $logger;
    private $table = 'client';
    private $othersClass;
    public $style = 'width:100%;';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $fields = ['clientname'];
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
            'load' => 0
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $parent = $this->coreFunctions->getfieldvalue("client", "parent", "clientid=?", [$config['params']['tableid']]);
        $clientname = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$parent]);
        $this->modulename = 'LEADER - ' . $clientname;

        $getcols = [
            'clientname'
        ];

        $tab = [$this->gridname => ['gridcolumns' => $getcols]];

        foreach ($getcols as $key => $value) {
            $$value = $key;
        }
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][$clientname]['label'] = "Members";
        $obj[0][$this->gridname]['columns'][$clientname]['readonly'] = true;

        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }


    public function createtabbutton($config)
    {
        return [];
    }


    public function add($config)
    {
        return [];
    }

    public function saveallentry($config)
    {
        return [];
    } // end function

    public function save($config)
    {
        return [];
    } //end function

    public function delete($config)
    {
        return [];
    }


    private function loaddataperrecord($config, $line)
    {
        return [];
    }

    public function loaddata($config)
    {
        $clientid = $config['params']['tableid'];

        $qry = "select clientname from client where parent = ? and isagent = 1  ";

        $data = $this->coreFunctions->opentable($qry, [$clientid]);
        return $data;
    }

    // -> Print Function
    public function reportsetup($config)
    {
        return [];
    }


    public function createreportfilter()
    {
        return [];
    }

    public function reportparamsdata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        return [];
    }
} //end class
