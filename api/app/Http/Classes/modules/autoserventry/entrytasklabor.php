<?php

namespace App\Http\Classes\modules\autoserventry;

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

class entrytasklabor
{
    private $fieldClass;
    private $tabClass;
    private $sbcscript;
    public $modulename = 'Tasks';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'jobtask';
    private $othersClass;
    public $style = 'width:100%;';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $fields = ['code', 'jobcode', 'description', 'labor1', 'labor2', 'labor3', 'labor4', 'labor5'];
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
            'load' => 5856,
            'save' => 5856
        );
        return $attrib;
    }

    public function createTab($config)
    {

        $columns = ['action', 'description', 'code', 'jobcode', 'labor1', 'labor2', 'labor3', 'labor4', 'labor5'];
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

        $obj[0][$this->gridname]['columns'][$description]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";

        $obj[0][$this->gridname]['columns'][$code]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$jobcode]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$labor1]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$labor2]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$labor3]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$labor4]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$labor5]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";

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
        $username = $config['params']['user'];
        $data = [];
        $data['line'] = 0;
        $data['code'] = '';
        $data['jobcode'] = '';
        $data['description'] = '';
        $data['labor1'] = 0;
        $data['labor2'] = 0;
        $data['labor3'] = 0;
        $data['labor4'] = 0;
        $data['labor5'] = 0;
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    private function selectqry()
    {
        $qry = "jt.line,jt.code,jt.jobcode,jt.description,jt.labor1,jt.labor2,jt.labor3,jt.labor4,jt.labor5";
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
                if ($data[$key]['line'] == 0) {
                    $data2['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['encodedby'] = $config['params']['user'];
                    $line = $this->coreFunctions->insertGetId($this->table, $data2);
                    $this->logger->sbcmasterlog($line, $config, ' CREATE - Task Description :' . $data[$key]['description'] . ''
                        . ' , Code: ' . $data[$key]['code']
                        . ' , Job Code: ' . $data[$key]['jobcode']
                        . ' , Labor 1: ' . $data[$key]['labor1']
                        . ' , Labor 2: ' . $data[$key]['labor2']
                        . ' , Labor 3: ' . $data[$key]['labor3']
                        . ' , Labor 4: ' . $data[$key]['labor4']
                        . ' , Labor 5: ' . $data[$key]['labor5']);
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
        $data = [];
        $row = $config['params']['row'];

        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }

        if ($row['line'] == 0) {
            $data['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
            $data['encodedby'] = $config['params']['user'];
            $line = $this->coreFunctions->insertGetId($this->table, $data);
            if ($line != 0) {
                $returnrow = $this->loaddataperrecord($line);
                $this->logger->sbcmasterlog(
                    $line,
                    $config,
                    ' CREATE - Task Description :' . $row['description'] . ''
                        . ' , Code: ' . $row['code']
                        . ' , Job Code: ' . $row['jobcode']
                        . ' Labor 1: ' . $row['labor1']
                        . ' Labor 2: ' . $row['labor2']
                        . ' Labor 3: ' . $row['labor3']
                        . ' Labor 4: ' . $row['labor4']
                        . ' Labor 5: ' . $row['labor5']

                );
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
        $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE - ' . $row['description'] . '' . ' , Code: ' . $row['code'] . ' , Job Code: ' . $row['jobcode']);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }


    private function loaddataperrecord($line)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " as jt 
        where jt.line=?";
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
        $searcfield =  ['jt.code', 'jt.jobcode', 'jt.description'];
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
        $qry = "select " . $select . " from " . $this->table . " as jt
        where 1=1 $filtersearch order by jt.line $l";
        $data = $this->coreFunctions->opentable($qry);
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
} //end class
