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

class issuedclearance
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'ISSUED CLEARANCE';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $logger;
    public $hhead = 'glhead';
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
            'load' => 0
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $columns = ['dateid', 'docno', 'purpose', 'rem', 'yourref', 'ourref'];
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
        $obj[0][$this->gridname]['columns'][$dateid]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$docno]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$purpose]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$rem]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$yourref]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$ourref]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";

        $obj[0][$this->gridname]['columns'][$docno]['label'] = "Brgy. Cert";
        $obj[0][$this->gridname]['columns'][$dateid]['label'] = "Issued Date";
        $obj[0][$this->gridname]['columns'][$purpose]['label'] = "Purpose";
        $obj[0][$this->gridname]['columns'][$rem]['label'] = "Purpose Detail";
        $obj[0][$this->gridname]['columns'][$yourref]['label'] = "RC No";
        $obj[0][$this->gridname]['columns'][$ourref]['label'] = "RC Place";

        $obj[0][$this->gridname]['columns'][$docno]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$dateid]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$purpose]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$rem]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$yourref]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$ourref]['type'] = "label";
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
        $qry = "select   head.dateid, concat('BC','-',right(head.docno,5)) as docno,
                locl.clearance as purpose,head.rem,
                head.yourref, head.ourref, head.clientid
            from " . $this->hhead . " as head
            left join locclearance as locl on locl.line = head.purposeid
            where head.doc = 'BD' and head.clientid=$tableid
            order by head.dateid, head.docno";
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }
} //end class