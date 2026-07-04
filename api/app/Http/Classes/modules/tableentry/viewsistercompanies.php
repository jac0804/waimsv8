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

class viewsistercompanies
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'CUSTOMER LIST';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $logger;
    private $table = 'client';
    private $othersClass;
    public $style = 'width:100%';
    public $tablelogs = 'client_log';
    public $tablelogs_del = 'del_client_log';
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
        $tableid = $config['params']['tableid'];
        // $parentcode = $this->coreFunctions->getfieldvalue("client", "grpcode", "clientid=?", [$tableid]);
        // $parentid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$parentcode]);
        // $parentname = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$parentid]);
        // $descriptions =$parentcode . ' - ' . $parentname;

        $columns = ['code', 'clientname','client','customer'];
        foreach ($columns as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $columns   ] ];

        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$code]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$client]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$customer]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";

        $obj[0][$this->gridname]['columns'][$code]['label'] = "Parent Code & Type";
        $obj[0][$this->gridname]['columns'][$code]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$clientname]['label'] = "Parent Name";
        $obj[0][$this->gridname]['columns'][$clientname]['readonly'] =true;
        $obj[0][$this->gridname]['columns'][$client]['type'] = "input";
        $obj[0][$this->gridname]['columns'][$client]['label'] = "Customer Code & Type";
        $obj[0][$this->gridname]['columns'][$client]['readonly'] =true;
        $obj[0][$this->gridname]['columns'][$customer]['label'] = "Customer Name";
        $obj[0][$this->gridname]['columns'][$customer]['readonly'] =true;
        // $this->modulename .= ' - ' . $descriptions;
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
        $parentcode = $this->coreFunctions->getfieldvalue("client", "grpcode", "clientid=?", [$tableid]);
        $parentid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$parentcode]);

        $qry="select concat(parent.type,' - ',parent.client) as code, parent.clientname, 
                concat(parent.client,' - ',parent.clientname) as description,
                concat(cl.type,' - ',cl.client) as client, cl.clientname as customer
                from client as cl
                left join client as parent on parent.client=cl.grpcode
                where parent.clientid=$parentid and cl.clientid <>$parentid";
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }
} //end class