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
use App\Http\Classes\sbcscript\sbcscript;


use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class entrycarton
{
    private $fieldClass;
    private $tabClass;
    private $sbcscript;
    public $modulename = 'Brand Size Per Carton';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'carton';
    private $othersClass;
    public $style = 'width:100%;';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $fields = ['brandid', 'qty', 'sizeid', 'carton'];
    public $showclosebtn = false;
    private $reporter;
    private $logger;
    public $rowperpage = 25;
    private $reportheader;
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
        $this->reportheader = new reportheader;
        $this->sbcscript = new sbcscript;
        // $this->lookupClass = new lookupClass;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 5815,
            'save' => 5815
        );
        return $attrib;
    }

    public function createTab($config)
    {

        $columns = ['action', 'brand_desc', 'sizeid', 'qty', 'carton', 'category'];
        $tab = [
            $this->gridname => [
                'gridcolumns' => $columns
            ]
        ];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }
        $stockbuttons = ['save', 'delete'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:50px;whiteSpace: normal;min-width:50px;";
        $obj[0][$this->gridname]['columns'][$carton]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$sizeid]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$sizeid]['action'] = "lookupsetup";
        $obj[0][$this->gridname]['columns'][$brand_desc]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$brand_desc]['type'] = "lookup";
        $obj[0][$this->gridname]['columns'][$brand_desc]['action'] = "lookupsetup";
        $obj[0][$this->gridname]['columns'][$brand_desc]['lookupclass'] = "lookupbrand";
        $obj[0][$this->gridname]['columns'][$qty]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$qty]['label'] = "Quantity";
        $obj[0][$this->gridname]['columns'][$category]['type'] = "hidden";
        $obj[0][$this->gridname]['columns'][$category]['label'] = "";
        $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);

        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['addrecord', 'saveallentry', 'whlog'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function add($config)
    {
        $data = [];
        $data['line'] = 0;
        $data['brand_desc'] = '';
        $data['brandid'] = 0;
        $data['sizeid'] = '';
        $data['qty'] = 0;
        $data['carton'] = 0;
        $data['bgcolor'] = 'bg-blue-2';
        $data['category'] = '';
        return $data;
    }

    private function selectqry()
    {
        $qry = "ct.line,ct.brandid,ct.qty,ct.sizeid,ct.carton,ct.sizeid as category ";
        return $qry;
    }

    public function saveallentry($config)
    {
        $companyid = $config['params']['companyid'];
        $data = $config['params']['data'];

        $dateTables = ['carton'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfieldFast($value2, $data[$key][$value2], $lookups);
                }
                if ($data[$key]['line'] == 0) {
                    $line = $this->coreFunctions->insertGetId($this->table, $data2);
                    $this->logger->sbcmasterlog($line, $config, ' CREATE - Brand :' . $data[$key]['brand_desc'] . '' . ' , Size: ' . $data[$key]['sizeid'] . ' ,  Quantity: ' . $data[$key]['qty'] . ' Carton' . $data[$key]['carton']);
                } else {
                    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['editby'] = $config['params']['user'];
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
    } // end function

    public function save($config)
    {
        $companyid = $config['params']['companyid'];
        $data = [];
        $row = $config['params']['row'];

        $dateTables = ['carton'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfieldFast($value, $row[$value], $lookups);
        }

        if ($row['line'] == 0) {
            $line = $this->coreFunctions->insertGetId($this->table, $data);
            if ($line != 0) {
                $returnrow = $this->loaddataperrecord($line);
                $this->logger->sbcmasterlog($line, $config, ' CREATE - Brand :' . $row['brand_desc'] . '' . ' , Size: ' . $row['sizeid'] . ' , Quantity: ' . $row['qty'] . ' Carton' . $row['carton']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];

            if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
                $returnrow = $this->loaddataperrecord($row['line']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        }
    } //end function

    public function delete($config)
    {
        $row = $config['params']['row'];
        $qry = "delete from " . $this->table . " where line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
        $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE - ' . $row['brand_desc'] . '' . ' , Size: ' . $row['sizeid'] . ' , Quantity: ' . $row['qty'] . ' Carton: ' . $row['carton']);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }


    private function loaddataperrecord($line)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . ",b.brand_desc from " . $this->table . " as ct 
        left join frontend_ebrands as b on b.brandid=ct.brandid
        where ct.line=?";
        $data = $this->coreFunctions->opentable($qry, [$line]);
        return $data;
    }

    public function loaddata($config)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $company = $config['params']['companyid'];
        $limit = '';
        $filtersearch = "";
        $searcfield =  ['ct.qty', 'ct.sizeid', 'ct.carton'];
        $search = '';

        if (isset($config['params']['filter'])) {
            $search = $config['params']['filter'];
            foreach ($searcfield as $key => $sfield) {
                if ($filtersearch == "") {
                    $filtersearch .= " and (" . $sfield . " like '%" . $search . "%' or b.brand_desc like '%" . $search . "%'";
                } else {
                    $filtersearch .= " or " . $sfield . " like '%" . $search . "%' or b.brand_desc like '%" . $search . "%'";
                } //end if
            }
            $filtersearch .= ")";
        }

        if ($search != "") {
            $l = '';
        } else {
            $l = $limit;
        }
        $qry = "select " . $select . ",b.brand_desc from " . $this->table . " as ct
        left join frontend_ebrands as b on b.brandid=ct.brandid
        where 1=1 $filtersearch order by ct.line $l";
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }

    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'whlog':
                return $this->lookuplogs($config);
                break;
            case 'lookupbrand':
                return $this->lookupbrand($config);
                break;
            case 'lookupsize':
                return $this->lookupsize($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
                break;
        }
    }

    public function lookuplogs($config)
    {
        $doc = $config['params']['doc'];
        $lookupsetup = array(
            'type' => 'show',
            'title' => 'Logs',
            'style' => 'width:1000px;max-width:1000px;'
        );

        // lookup columns
        $cols = array(
            array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')
        );

        // $trno = $config['params']['tableid'];

        $qry = "
        select trno, doc, task, log.user, dateid, 
        if(pic='','blank_user.png',pic) as pic
        from " . $this->tablelogs . " as log
        left join useraccess as u on u.username=log.user
        where log.doc = '" . $doc . "'
        union all
        select trno, doc, task, log.user, dateid, 
        if(pic='','blank_user.png',pic) as pic
        from  " . $this->tablelogs_del . " as log
        left join useraccess as u on u.username=log.user
        where log.doc = '" . $doc . "'";

        $qry = $qry . " order by dateid desc";
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
    }
    public function lookupbrand($config)
    {

        $plotting = array('brandid' => 'brandid', 'brand_desc' => 'brand');
        $plottype = 'plotgrid';
        $title = 'List of Brand';

        $lookupsetup = array(
            'type' => 'single',
            'title' => $title,
            'style' => 'width:900px;max-width:900px;'
        );
        $plotsetup = array(
            'plottype' => $plottype,
            'action' => '',
            'plotting' => $plotting
        );
        // lookup columns
        $cols = [
            ['name' => 'brand', 'label' => 'Brand', 'align' => 'left', 'field' => 'brand', 'sortable' => true, 'style' => 'font-size:16px;']
        ];


        $qry = "select brandid , brand_desc as brand from frontend_ebrands";
        $data = $this->coreFunctions->opentable($qry);
        $index = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
    } //end function
    public function lookupsize($config)
    {
        $plotting = array('sizeid' => 'sizeid');
        $plottype = 'plotgrid';
        $title = 'List of Sizes';

        $lookupsetup = array(
            'type' => 'single',
            'title' => $title,
            'style' => 'width:900px;max-width:900px;'
        );
        $plotsetup = array(
            'plottype' => $plottype,
            'action' => '',
            'plotting' => $plotting
        );
        // lookup columns
        $cols = [
            ['name' => 'sizeid', 'label' => 'Size', 'align' => 'left', 'field' => 'sizeid', 'sortable' => true, 'style' => 'font-size:16px;']
        ];

        $qry = "select distinct sizeid  from item ";
        $data = $this->coreFunctions->opentable($qry);
        $index = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
    } //end function
} //end class
