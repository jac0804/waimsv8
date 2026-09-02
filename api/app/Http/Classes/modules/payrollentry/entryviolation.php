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
use App\Http\Classes\SBCPDF;
use App\Http\Classes\reportheader;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class entryviolation
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Violation Setup';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'sgviolation';
    private $othersClass;
    public $style = 'width:100%;';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $fields = ['violation', 'offense', 'vaction', 'type'];
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
            'load' => 5959
            // 'save' => 5887
        );
        return $attrib;
    }

    public function createTab($config)
    {

        $columns = [
            'action',
            'violation',
            'offense',
            'vaction',
            'type'
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
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:50px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$violation]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$violation]['readonly'] = false;
        $obj[0][$this->gridname]['columns'][$offense]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$offense]['readonly'] = false;
        $obj[0][$this->gridname]['columns'][$vaction]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$vaction]['readonly'] = false;
        $obj[0][$this->gridname]['columns'][$type]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$type]['label'] = 'Type';
        $obj[0][$this->gridname]['columns'][$type]['readonly'] = false;

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
        $searcfield = $this->fields; // real DB columns, correct for WHERE clause
        $filtersearch = "";
        if (isset($config['params']['filter'])) {
            $search = $config['params']['filter'];
            foreach ($searcfield as $key => $sfield) {
                if ($filtersearch == "") {
                    $filtersearch .= " and (" . $sfield . " like '%" . $search . "%'";
                } else {
                    $filtersearch .= " or " . $sfield . " like '%" . $search . "%'";
                }
            }
            $filtersearch .= ")";
        }
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select distinct " . $select . " from " . $this->table . " where 1 = 1 " . $filtersearch . " order by line";
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }

    public function selectqry()
    {
        $qry = "sgviolation.line,sgviolation.violation, sgviolation.offense, sgviolation.vaction, sgviolation.type";
        return $qry;
    }

    public function add($config)
    {
        $data = [];
        $data['line'] = 0;
        $data['violation'] = '';
        $data['offense'] = '';
        $data['vaction'] = '';
        $data['type'] = '';
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    public function save($config)
    {
        $data = [];
        $row = $config['params']['row'];
        $companyid = $config['params']['companyid'];

        $dateTables = ['sgviolation'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

        foreach ($this->fields as $key => $field) {
            $value = isset($row[$field]) ? $row[$field] : '';
            $data[$field] = $this->othersClass->sanitizekeyfieldFast($field, $value, $lookups);
        }

        if (empty(trim($data['violation']))) {
            return ['status' => false, 'msg' => 'Saving failed. Please complete the violation field.'];
        }

        $lineValue = isset($row['line']) ? $row['line'] : 0;

        if ($lineValue == 0) {
            $data['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
            $data['encodedby'] = $config['params']['user'];
            $data['editby'] = $config['params']['user']; // NOT NULL column, must be set on insert
            $id = $this->coreFunctions->insertGetId($this->table, $data);
            if ($id != 0) {
                $returnrow = $this->loaddataperrecord($id);
                $this->logger->sbcmasterlog(
                    $id,
                    $config,
                    ' CREATE - Violation : ' . $row['violation'] . ' - ' . 'Offense : ' . $row['offense'] . ' - ' . 'Action : ' . $row['vaction'] . ' - ' . 'Type : ' . $row['type']
                );
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];

            if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $lineValue]) == 1) {
                $returnrow = $this->loaddataperrecord($lineValue);
                $this->logger->sbcmasterlog(
                    $lineValue,
                    $config,
                    ' UPDATE - Violation : ' . $row['violation'] . ' - ' . 'Offense : ' . $row['offense'] . ' - ' . 'Action : ' . $row['vaction'] . ' - ' . 'Type : ' . $row['type']
                );
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        }
    } //end function

    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        $companyid = $config['params']['companyid'];

        $dateTables = ['sgviolation'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

        foreach ($data as $key => $rowvalue) {
            $data2 = [];
            if (!empty($data[$key]['bgcolor'])) {
                foreach ($this->fields as $key2 => $field) {
                    $value = isset($data[$key][$field]) ? $data[$key][$field] : '';
                    $data2[$field] = $this->othersClass->sanitizekeyfieldFast($field, $value, $lookups);
                }

                if (empty(trim($data2['violation']))) {
                    return ['status' => false, 'msg' => 'Saving failed. Please complete the empty violation(s).'];
                }

                $lineValue = isset($data[$key]['line']) ? $data[$key]['line'] : 0;

                if ($lineValue == 0) {
                    $data2['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['encodedby'] = $config['params']['user'];
                    $data2['editby'] = $config['params']['user']; // NOT NULL column, must be set on insert
                    $id = $this->coreFunctions->insertGetId($this->table, $data2);
                    $this->logger->sbcmasterlog(
                        $id,
                        $config,
                        ' CREATE - Violation : ' . $data[$key]['violation'] . ' - ' . 'Offense : ' . $data[$key]['offense'] . ' - ' . 'Action : ' . $data[$key]['vaction'] . ' - ' . 'Type : ' . $data[$key]['type']
                    );
                } else {
                    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['editby'] = $config['params']['user'];
                    $this->coreFunctions->sbcupdate(
                        $this->table,
                        $data2,
                        ['line' => $lineValue]
                    );
                    $this->logger->sbcmasterlog(
                        $lineValue,
                        $config,
                        ' UPDATE - Violation : ' . $data[$key]['violation'] . ' - ' . 'Offense : ' . $data[$key]['offense'] . ' - ' . 'Action : ' . $data[$key]['vaction'] . ' - ' . 'Type : ' . $data[$key]['type']
                    );
                }
            }
        }
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved Successfully.', 'data' => $returndata];
    } // end function

    public function delete($config)
    {
        $row = $config['params']['row'];
        $qry = "delete from " . $this->table . " where line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
        $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE - Violation : ' . $row['violation'] . ' - ' . 'Offense : ' . $row['offense'] . ' - ' . 'Action : ' . $row['vaction'] . ' - ' . 'Type : ' . $row['type']);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }

    private function loaddataperrecord($id)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " 
        where line = ?";
        $data = $this->coreFunctions->opentable($qry, [$id]);
        return $data;
    }

    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'whlog':
                return $this->lookuplogs($config);
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
}//end class