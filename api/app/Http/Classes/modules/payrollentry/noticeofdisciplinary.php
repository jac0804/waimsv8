<?php

namespace App\Http\Classes\modules\payrollentry;

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

class noticeofdisciplinary
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'NOTICE OF DISCIPLINARY ACTION';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'hdisciplinary';
    private $othersClass;
    public $style = 'width:1100;';
    public $showclosebtn = false;


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

        $columns = ['docno', 'dateid', 'irno', 'idescription', 'code', 'articlename', 'section', 'description', 'violationno', 'penalty', 'numdays', 'startdate', 'enddate', 'amt', 'detail'];
        $tab = [$this->gridname => ['gridcolumns' => $columns]];
        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $stockbuttons = [];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$docno]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$dateid]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$irno]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$idescription]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$idescription]['label'] = "Incident Description";

        $obj[0][$this->gridname]['columns'][$code]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$code]['label'] = "Article";

        $obj[0][$this->gridname]['columns'][$section]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$section]['label'] = "Section";

        $obj[0][$this->gridname]['columns'][$articlename]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$description]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$description]['label'] = "Section Description";
        $obj[0][$this->gridname]['columns'][$violationno]['type'] = "label";

        $obj[0][$this->gridname]['columns'][$penalty]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$numdays]['type'] = "label";

        $obj[0][$this->gridname]['columns'][$startdate]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$enddate]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$startdate]['label'] = "Start Date";
        $obj[0][$this->gridname]['columns'][$enddate]['label'] = "End Date";

        $obj[0][$this->gridname]['columns'][$amt]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$detail]['type'] = "label";

        $obj[0][$this->gridname]['columns'][$docno]['style'] = "width:50px;whiteSpace: normal;min-width:50px; text-align: left;";
        $obj[0][$this->gridname]['columns'][$dateid]['style'] = "width:100px;whiteSpace: normal;min-width:100px; text-align: left;";
        $obj[0][$this->gridname]['columns'][$irno]['style'] = "width:30px;whiteSpace: normal;min-width:30px;";
        $obj[0][$this->gridname]['columns'][$idescription]['style'] = "width:30px;whiteSpace: normal;min-width:30px;";
        $obj[0][$this->gridname]['columns'][$code]['style'] = "width:50px;whiteSpace: normal;min-width:50px; text-align: left;";

        $obj[0][$this->gridname]['columns'][$description]['style'] = "width:200px;whiteSpace: normal;min-width:200px; text-align: left;";
        $obj[0][$this->gridname]['columns'][$penalty]['style'] = "width:120px;whiteSpace: normal;min-width:120px; text-align: left;";
        $obj[0][$this->gridname]['columns'][$numdays]['style'] = "width:50px;whiteSpace: normal;min-width:50px; text-align: left;";
        $obj[0][$this->gridname]['columns'][$startdate]['style'] = "width:100px;whiteSpace: normal;min-width:100px; text-align: left;";
        $obj[0][$this->gridname]['columns'][$enddate]['style'] = "width:100px;whiteSpace: normal;min-width:100px; text-align: left;";
        $obj[0][$this->gridname]['columns'][$amt]['style'] = "width:50px;whiteSpace: normal;min-width:50px; text-align: right;";


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
        $qry = "select head.docno, date(head.dateid) as dateid,ir.docno as irno,
                ir.idescription,chead.code,chead.description as articlename, 
                cdetail.section, cdetail.description,head.violationno, head.penalty, head.numdays,
                head.startdate, head.enddate,head.amt, head.detail
            from " . $this->table . " as head
            left join hincidenthead as ir on head.refx=ir.trno
            left join codehead as chead on chead.artid=head.artid
            left join codedetail as cdetail on head.sectionno=cdetail.line and chead.artid=cdetail.artid where head.empid=?";

        $data = $this->coreFunctions->opentable($qry, [$tableid]);
        return $data;
    }
} //end class
