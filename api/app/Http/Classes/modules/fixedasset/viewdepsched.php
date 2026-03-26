<?php

namespace App\Http\Classes\modules\fixedasset;

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

class viewdepsched
{

    private $fieldClass;
    private $tabClass;
    public $modulename = 'Schedule';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'fasched';
    private $othersClass;
    private $logger;
    public $style = 'width:100%;';

    public $showclosebtn = false;

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
        $attrib = array('load' => 79);
        return $attrib;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }


    public function createTab($config)
    {
        $columns = ['dateid',  'amt', 'docno', 'itemname'];
        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $columns]];

        $stockbuttons = [];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$docno]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$amt]['type'] = 'label';

        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function loaddata($config)
    {

        $qry = "select date(fa.dateid) as dateid, fa.amt, ifnull(num.docno,'') as docno, '' as itemanme from fasched as fa left join cntnum as num on num.trno=fa.jvtrno where fa.rrtrno=? order by dateid";
        $data = $this->coreFunctions->opentable($qry, [$config['params']['tableid']]);
        return $data;
    }
}
