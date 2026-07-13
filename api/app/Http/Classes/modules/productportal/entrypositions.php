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

class entrypositions
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
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:50px;whiteSpace: normal;min-width:50px;";
        $obj[0][$this->gridname]['columns'][$position]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";

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
        $searcfield = $this->fields; // ['positions'] - real DB column, correct for WHERE clause
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
        $qry = "select " . $select . " from " . $this->table . " where 1 = 1 " . $filtersearch . " order by id";
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }

    public function selectqry()
    {
        $qry = "positions.positions as position, id";
        return $qry;
    }

    public function add($config)
    {
        $data = [];
        $data['id'] = 0;
        $data['position'] = '';
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        foreach ($data as $key => $value) {
            $data2 = [];
            if (!empty($data[$key]['bgcolor'])) {
                foreach ($this->fields as $key2 => $field) {
                    $value = isset($data[$key]['position']) ? $data[$key]['position'] : '';
                    $data2[$field] = $this->othersClass->sanitizekeyfield($field, $value);
                }
                if (empty(trim($data2['positions']))) {
                    return ['status' => false, 'msg' => 'Saving failed. Please complete the empty positions.'];
                }
                $idValue = isset($data[$key]["id"]) ? $data[$key]['id'] : 0;
                if ($idValue === 0) {
                    $data2['createdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['createby'] = $config['params']['user'];
                    $id = $this->coreFunctions->insertGetId($this->table, $data2);
                    $this->logger->sbcmasterlog(
                        $id,
                        $config,
                        ' CREATE - Position : ' . $data[$key]['position']
                    );
                } else {
                    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['editby'] = $config['params']['user'];
                    $this->coreFunctions->sbcupdate(
                        $this->table,
                        $data2,
                        ['id' => $data[$key]['id']]
                    );
                    $this->logger->sbcmasterlog(
                        $data[$key]['id'],
                        $config,
                        ' UPDATE - Position : ' . $data[$key]['position']
                    );
                }
            }
        }
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved Successfully.', 'data' => $returndata];
    }

    public function save($config)
    {
        $data = [];
        $row = $config['params']['row'];
        foreach ($this->fields as $key => $value) {
            // row[position] - eto yung value na isesave
            // $data['positions'] - this is where the value will be save
            $data['positions'] = $this->othersClass->sanitizekeyfield($value, $row['position']);
        }

        if ($row['id'] == 0) {
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $id = $this->coreFunctions->insertGetId($this->table, $data);
            if ($id != 0) {
                $returnrow = $this->loaddataperrecord($id);
                $this->logger->sbcmasterlog(
                    $id,
                    $config,
                    ' CREATE - Position : ' . $row['position']

                );
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];

            if ($this->coreFunctions->sbcupdate($this->table, $data, ['id' => $row['id']]) == 1) {
                $returnrow = $this->loaddataperrecord($row['id']);
                $this->logger->sbcmasterlog(
                    $row['id'],
                    $config,
                    ' UPDATE - Position : ' . $row['position']
                );
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        }
    } //end function

    public function delete($config)
    {
        $row = $config['params']['row'];
        $qry = "delete from " . $this->table . " where id=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['id']]);
        $this->logger->sbcdelmaster_log($row['id'], $config, 'REMOVE - Posistion : ' . $row['position']);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }

    private function loaddataperrecord($id)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " 
        where id = ?";
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
}//end fn
