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

class viewndahistorydetails
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'DETAILS';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'hdisciplinary';
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['dateid', 'dateeffect', 'dateend', 'remarks', 'basicrate', 'type'];
    public $showclosebtn = false;
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
        $attrib = array(
            'load' => 0
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $getcols = [
            'docno', 'memodocno', 'gudescription', 'description', 'penalty', 'startdate', 'enddate', 'amt',
            'details', 'findings'
        ];

        foreach ($getcols as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $getcols]];

        $stockbuttons = [];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$docno]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$memodocno]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$gudescription]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$description]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$penalty]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$startdate]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$enddate]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$amt]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$details]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$findings]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$gudescription]['label'] = 'Article Description';
        $obj[0][$this->gridname]['columns'][$description]['label'] = 'Section Description';
        $obj[0][$this->gridname]['columns'][$startdate]['label'] = 'Start Date';
        $obj[0][$this->gridname]['columns'][$amt]['align'] = 'text-left';

        $obj[0][$this->gridname]['columns'][$docno]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $obj[0][$this->gridname]['columns'][$memodocno]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $obj[0][$this->gridname]['columns'][$gudescription]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
        $obj[0][$this->gridname]['columns'][$description]['style'] = 'width:500px;whiteSpace: normal;min-width:500px;';
        $obj[0][$this->gridname]['columns'][$penalty]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $obj[0][$this->gridname]['columns'][$startdate]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';
        $obj[0][$this->gridname]['columns'][$enddate]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';
        $obj[0][$this->gridname]['columns'][$amt]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';
        $obj[0][$this->gridname]['columns'][$details]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $obj[0][$this->gridname]['columns'][$findings]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';

        return $obj;
    }


    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }

    private function selectqry()
    {
        $qry = " nda.docno,memo.docno as memodocno,cc.description as gudescription,
                cd.description as description,nda.penalty,nda.startdate,nda.enddate,nda.amt, nda.detail as details,nda.findings";

        return $qry;
    }

    public function loaddata($config)
    {
        $empid = $config['params']['tableid'];
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . "
                from " . $this->table . " as nda
                left join hincidenthead as memo on memo.trno=nda.refx
                left join codehead as cc on cc.artid=nda.artid
                left join codedetail as cd on cd.artid=cc.artid and cd.line=nda.sectionno
                where nda.empid=? order by nda.trno desc";
        $data = $this->coreFunctions->opentable($qry, [$empid]);
        return $data;
    }
} //end class
