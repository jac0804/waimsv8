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

class viewquestions
{

    private $fieldClass;
    private $tabClass;
    public $modulename = 'QUESTION LIST';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'qnstock';
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['description'];
    public $tablelogs = 'masterfile_log';
    public $showclosebtn = true;
    private $enrollmentlookup;
    private $logger;

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
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createTab($config)
    {
        $column = ['action', 'section', 'question', 'points', 'a', 'b', 'c', 'd', 'e', 'ans', 'answord'];

        foreach ($column as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $column]];
        $stockbuttons = ['delete', 'editquestion'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$section]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][$question]['style'] = "width:400px;whiteSpace: normal;min-width:400px;";
        $obj[0][$this->gridname]['columns'][$points]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";

        $obj[0][$this->gridname]['columns'][$section]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$question]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$points]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$a]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$b]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$c]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$d]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$e]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$ans]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$answord]['type'] = "label";

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
        $trno = $config['params']['tableid'];
        $qry = 'select qid, line, sortline, section, question, a, b, c, d, e, ans, answord, points, objtype from qnstock where qid=? order by section, sortline, line';
        $data = $this->coreFunctions->opentable($qry, [$trno]);
        return $data;
    }
}
