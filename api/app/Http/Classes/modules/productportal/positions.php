<?php

namespace App\Http\Classes\modules\productportal;

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

class positions
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Positions';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'positions';
    private $othersClass;
    public $style = 'width:100%;';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $fields = ['positions'];
    public $showclosebtn = false;
    private $reporter;
    public $logger;
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
        $attrib = array(
            'load' => 5887
            // 'save' => 5887
        );
        return $attrib;
    }

    public function createTab($config)
    {

        $columns = [
            'action',
            'position'
        ];

        foreach ($columns as $key => $value) {
            $$value = $key; //declare
        }

        $stockbuttons = ['save', 'delete'];
        $tab = [
            $this->gridname => [
                'gridcolumns' => $columns
            ]
        ];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$position]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";

        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['addrecord', 'saveallentry', 'whlog']; // tab button
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function loaddata($config)
    {
        $searcfield = $this->fields;
        $filtersearch = "";
        if (isset($config['params']['filter'])) {
            $search = $config['params']['filter'];
            foreach ($searcfield as $key => $sfield) {
                if ($filtersearch == "") {
                    $filtersearch .= " and (" . $sfield . " like '%" . $search . "%'";
                } else {
                    $filtersearch .= " or " . $sfield . " like '%" . $search . "%'";
                } //end if
            }
            $filtersearch .= ")";
        }
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select distinct " . $select . " from " . $this->table . "  where 1 = 1 " . $filtersearch . " order by id";
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }

    public function selectqry()
    {
        $qry = "positions.positions";

        return $qry;
    }

    public function add($config)
    {
        $data = [];
        $data['id'] = 0;
        $data['positions'] = '';
        $data['bgcolor'] = 'bg-green-2';
        return $data;
    }
}//end fn
