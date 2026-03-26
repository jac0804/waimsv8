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

class  tabrecruitprocess
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'STATUS HISTORY';
    public $tablenum = 'transnum';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'app';
    private $othersClass;
    public $style = 'width:100%;max-width: 100%';
    private $fields = ['trno', 'dateid3', 'remarks'];
    public $showclosebtn = true;
    public $tablelogs = 'masterfile_log';
    public $statlogs = 'app_stat';
    public $tablelogs_del = 'del_client_log';
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
        $columns = ['description', 'date2', 'date3', 'remarks'];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $columns]];
        $stockbuttons = [];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$description]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][$date2]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$date3]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$remarks]['style'] = "width:500px;whiteSpace: normal;min-width:500px;";

        $obj[0][$this->gridname]['columns'][$date2]['label'] = 'Date Start';
        $obj[0][$this->gridname]['columns'][$date3]['label'] = 'Date End';
        $obj[0][$this->gridname]['columns'][$remarks]['label'] = 'Notation (Background Checking)';

        $obj[0][$this->gridname]['columns'][$description]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$date2]['type'] = 'label';
        // $obj[0][$this->gridname]['columns'][$date3]['name'] = 'dateid3';
        // $obj[0][$this->gridname]['columns'][$date3]['type'] = 'label';
        // $obj[0][$this->gridname]['columns'][$remarks]['type'] = 'label';

        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['saveallentry'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function saveallentry($config)
    {
        $trno = $config['params']['tableid'];
        $data = $config['params']['data'];
        foreach ($data as $key => $value) {
            $data2 = [];

            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }
                // $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                $data2['dateid3'] = $data[$key]['date3'];

                if ($data[$key]['oldversion'] != '') {
                    $this->coreFunctions->sbcupdate($this->statlogs, $data2, ['oldversion' => $data[$key]['oldversion'], 'trno' => $data[$key]['trno']]);
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata, 'reloadhead' => true];
    } // end function

    public function loaddata($config)
    {
        $doc = $config['params']['doc'];
        $trno = $config['params']['tableid'];
        $qry = "select trno,oldversion,
         case when oldversion = 'Tag for Background Checking.' then 'BACKGROUND CHECKING'
              when oldversion = 'Tag for Final Interview.' then 'FINAL INTERVIEW'
              when oldversion = 'Tag for Hiring & Pre-Employment Requirements.' then 'HIRING & PRE-EMPLOYMENT REQUIREMENTS'
              else oldversion
                END as description,if(dateid2 is null,dateid,date(dateid2)) as date2,date(dateid3) as date3,date(dateid3) as dateid3, remarks,'' as bgcolor
        from app_stat where trno=? order by dateid asc";

        $data = $this->coreFunctions->opentable($qry, [$trno]);
        return $data;
    }
}
