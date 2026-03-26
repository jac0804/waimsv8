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
use App\Http\Classes\lookup\enrollmentlookup;

class entryskillreq_viewing
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'SKILL DESCRIPTION';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'jobtskills';
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['skills'];
    public $showclosebtn = true;
    private $enrollmentlookup;

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
        $tab = [$this->gridname => ['gridcolumns' => ['description']]];

        $stockbuttons = [];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][0]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][0]['style'] = "width:1000px;whiteSpace: normal;min-width:1000px;";


        return $obj;
    }

    public function createtabbutton($config)
    {
        return 0;
    }


    private function selectqry()
    {
        $qry = "line";
        foreach ($this->fields as $key => $value) {
            $qry = $qry . ',' . $value;
        }
        return $qry;
    }

    private function loaddataperrecord($trno, $line)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " where trno=? and line=?";
        $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
        return $data;
    }

    public function loaddata($config)
    {
        $trno = $config['params']['tableid'];
        $data = $this->getjobdesc($trno);
        return $data;
    }


    private function getjobdesc($trno)
    {
        $qry = "
            select s.skill as description from jobthead AS jh
            left join jobtskills as js ON jh.line = js.trno
            left join skillrequire as s ON s.line = js.skills
            where docno = (select job from ( select trno, job from personreq 
            union all select trno, job from hpersonreq ) as d where trno = ?) 
        ";
        $data = $this->coreFunctions->opentable($qry, [$trno]);
        return $data;
    }
} //end class
