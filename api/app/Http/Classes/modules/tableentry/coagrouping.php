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
use App\Http\Classes\builder\lookupClass;

class coagrouping
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'COA GROUPING';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'coa';
    private $othersClass;
    public $style = 'width:100%;';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $fields = ['acnoid', 'acno', 'acnoname', 'incomegrp', 'isshow', 'iscompute', 'isparenttotal', 'isprojexp', 'isinactive'];
    public $showclosebtn = false;
    private $reporter;
    private $lookupClass;


    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->reporter = new SBCPDF;
        $this->logger = new Logger;
        $this->lookupClass = new lookupClass;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 3612
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $companyid = $config['params']['companyid'];
        $columns = ['acno', 'acnoname', 'incomegrp', 'isshow', 'iscompute', 'isparenttotal', 'isprojexp', 'isinactive'];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $columns]];
        $stockbuttons = [];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][$acno]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
        $obj[0][$this->gridname]['columns'][$acnoname]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$incomegrp]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
        $obj[0][$this->gridname]['columns'][$acno]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$acnoname]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$acnoname]['label'] = "Account Name";

        if ($companyid != 8) { //not maxipro
            $obj[0][$this->gridname]['columns'][$isinactive]['type'] = 'coldel';
        }

        switch ($companyid) {
            case 24: //goodfound
                $obj[0][$this->gridname]['columns'][$incomegrp]['type'] = 'coldel';
                $obj[0][$this->gridname]['columns'][$isshow]['type'] = 'coldel';
                $obj[0][$this->gridname]['columns'][$isparenttotal]['type'] = 'coldel';
                $obj[0][$this->gridname]['columns'][$isprojexp]['type'] = 'coldel';
                break;
            case 8: //maxipro
                $obj[0][$this->gridname]['columns'][$iscompute]['type'] = 'coldel';
                $obj[0][$this->gridname]['columns'][$isparenttotal]['type'] = 'coldel';
                break;
            default:
                $obj[0][$this->gridname]['columns'][$isprojexp]['type'] = 'coldel';
                break;
        }

        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }


    public function createtabbutton($config)
    {
        $tbuttons = ['saveallentry'];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }

    private function selectqry()
    {
        $qry = " acnoid,acno,acnoname,incomegrp, 
        case isshow when 0 then 'false' else 'true' end as isshow,
        case isparenttotal when 0 then 'false' else 'true' end as isparenttotal,
        case iscompute when 0 then 'false' else 'true' end as iscompute,
        case isprojexp when 0 then 'false' else 'true' end as isprojexp,
        case isinactive when 0 then 'false' else 'true' end as isinactive";
        return $qry;
    }

    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        foreach ($data as $key => $value) {
            $data2 = [];

            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }
                $this->coreFunctions->sbcupdate($this->table, $data2, ['acnoid' => $data[$key]['acnoid']]);
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
    } // end function 


    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
                break;
        }
    }

    public function loaddata($config)
    {
        $companyid = $config['params']['companyid'];

        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $limit = '';

        $addonfilter = '';
        if ($companyid == 24) { //goodfound
            $addonfilter = " and detail=0 and parent<>'\\\'";
        }

        $qry = "select " . $select . " from " . $this->table . " where ''='' " . $addonfilter . " order by acno $limit";
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }
} //end class
