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
use App\Http\Classes\reportheader;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class pvbudget
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'BUDGET';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['description'];
    public $showclosebtn = false;
    private $reporter;
    private $logger;
    private $reportheader;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->reporter = new SBCPDF;
        $this->logger = new Logger;
        $this->reportheader = new reportheader;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createTab($config)
    {
        $columns = ['acnoname', 'fdb', 'fcr', 'project', 'amount', 'amount2', 'amt'];
        $tab = [$this->gridname => ['gridcolumns' => $columns]];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$acnoname]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$acnoname]['label'] = "Account Name";
        $obj[0][$this->gridname]['columns'][$acnoname]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$fdb]['style'] = "width:80px;whiteSpace: normal;min-width:80px; text-align:left;";
        $obj[0][$this->gridname]['columns'][$fdb]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$fdb]['label'] = "Debit";
        $obj[0][$this->gridname]['columns'][$fcr]['style'] = "width:80px;whiteSpace: normal;min-width:80px; text-align:left;";
        $obj[0][$this->gridname]['columns'][$fcr]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$fcr]['label'] = "Credit";
        $obj[0][$this->gridname]['columns'][$project]['style'] = "width:100px;whiteSpace: normal;min-width:100px; text-align:left;";
        $obj[0][$this->gridname]['columns'][$project]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$project]['label'] = "Project";
        $obj[0][$this->gridname]['columns'][$amount]['style'] = "width:80px;whiteSpace: normal;min-width:80px; text-align:left;";
        $obj[0][$this->gridname]['columns'][$amount]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$amount]['label'] = "Month Total";

        $obj[0][$this->gridname]['columns'][$amount2]['style'] = "width:80px;whiteSpace: normal;min-width:80px; text-align:left;";
        $obj[0][$this->gridname]['columns'][$amount2]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$amount2]['label'] = "Budget";

        $obj[0][$this->gridname]['columns'][$amt]['style'] = "width:200px;whiteSpace: normal;min-width:200px; text-align:left;";
        $obj[0][$this->gridname]['columns'][$amt]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$amt]['label'] = "Difference";
        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function createtabbutton($config)
    {
        return 0;
    }


    public function loaddata($config)
    {
        $trno = $config['params']['tableid'];
        $data = $this->mainqry($config, $trno);
        return $data;
    }


    private function budget($config, $trno)
    {
        $qry = "select head.dateid,c.acnoid,head.projectid,detail.db,detail.cr from lahead as head
                left join ladetail as detail on detail.trno=head.trno
                left join coa as c on c.acnoid=detail.acnoid
                where head.trno=$trno and head.doc='PV'";

        $resmain = $this->coreFunctions->opentable($qry);

        $results = array();

        foreach ($resmain as $row) {

            $dateid = isset($row->dateid) ? $row->dateid : '';
            $projectid = isset($row->projectid) ? $row->projectid : 0;
            $acnoid = isset($row->acnoid) ? $row->acnoid : 0;
            $db = isset($row->db) ? $row->db : 0;
            $cr = isset($row->cr) ? $row->cr : 0;

            $month = (int) date('m', strtotime($dateid));
            $year  = date('Y', strtotime($dateid));

            $qry = "select amt" . $month . " as budget, $acnoid as acnoid, $projectid as projectid,$db as db, $cr as cr  from budget as b
                where b.year = $year and b.acnoid=$acnoid and b.projectid=$projectid";

            $data = $this->coreFunctions->opentable($qry);

            if (!empty($data)) {
                $results[] = $data[0];
            }
        }

        return $results;
    }


    private function mainqry($config, $trno)
    {
        $budgetres = $this->budget($config, $trno);
        $results = array();

        foreach ($budgetres as $row) {

            $acnoid = isset($row->acnoid) ? $row->acnoid : 0;
            $projectid = isset($row->projectid) ? $row->projectid : 0;
            $budget = isset($row->budget) ? $row->budget : 0;
            $db = isset($row->db) ? $row->db : 0;
            $cr = isset($row->cr) ? $row->cr : 0;

            $qry = "select  c.acnoname,proj.name as project,format(sum(detail.db), 2) as amount,
                       format($db,2) as fdb, format($cr,2) as fcr,$budget as amount2,  format($budget - sum(detail.db),2) as amt
                 from lahead as head
                left join ladetail as detail on detail.trno=head.trno
                left join coa as c on c.acnoid=detail.acnoid
                left join projectmasterfile as proj on proj.line=head.projectid where  head.doc='PV' and head.trno <=$trno
                and c.acnoid=$acnoid and head.projectid=$projectid
                group by c.acnoname,proj.name";
            $data = $this->coreFunctions->opentable($qry);

            foreach ($data as $row2) {
                $results[] = $row2;
            }
        }
        return $results;
    }

} //end class
