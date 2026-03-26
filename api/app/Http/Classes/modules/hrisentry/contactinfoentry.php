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

class contactinfoentry
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'CONTACT INFORMATION';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'contacts';
    public $tablelogs = 'masterfile_log';
    private $logger;
    private $othersClass;
    public $style = 'width:1100px;max-width:1100px;';
    private $fields =  ['empid', 'contact1', 'relation1', 'addr1', 'homeno1', 'mobileno1', 'officeno1', 'ext1', 'notes1'];
    public $showclosebtn = false;


    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
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

        $tab = [$this->gridname => ['gridcolumns' => ['action', 'contact1', 'relation1',  'addr1',  'homeno1',  'mobileno1',  'officeno1',  'ext1',  'notes1']]];

        $stockbuttons = ['save', 'delete'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][0]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][1]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][2]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][3]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][4]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][5]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
        $obj[0][$this->gridname]['columns'][6]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][7]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][8]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";



        return $obj;
    }


    public function createtabbutton($config)
    {
        $tbuttons = [];
        $tbuttons = ['addrecord', 'saveallentry', 'masterfilelogs'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function add($config)
    {
        $ids = $config['params']['tableid'];
        $id = $this->coreFunctions->datareader("select empid as value from employee where empid = ?  LIMIT 1", [$ids]);

        $data = [];
        $data['empid'] = $id;
        $data['line'] = 0;
        $data['contact1'] = '';
        $data['relation1'] = '';
        $data['addr1'] = '';
        $data['homeno1'] = '';
        $data['mobileno1'] = '';
        $data['officeno1'] = '';
        $data['ext1'] = '';
        $data['notes1'] = '';
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    private function selectqry()
    {
        $qry = "line, empid, contact1, relation1, addr1,homeno1,mobileno1,officeno1,ext1, notes1";
        return $qry;
    }

    public function save($config)
    {
        $data = [];
        $row = $config['params']['row'];
        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }

        if ($row['line'] == 0) {

            $line = $this->coreFunctions->insertGetId($this->table, $data);

            if ($line != 0) {
                $returnrow = $this->loaddataperrecord($row['empid'], $line, $config);

                $config['params']['doc'] = 'CONTACTINFOENTRY';
                $this->logger->sbcmasterlog(
                    $line,
                    $config,
                    'CREATE CONTACTPERSON - LINE: ' . $line . ' - NAME: ' . $data['contact1']
                );

                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];

            $update = $this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]);

            if ($update == 1) {
                $returnrow = $this->loaddataperrecord($row['empid'], $row['line'], $config);

                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        }
    } //end function

    public function saveallentry($config)
    {
        $empid = $config['params']['tableid'];
        $data = $config['params']['data'];
        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }
                if ($data[$key]['line'] == 0) {
                    $line = $this->coreFunctions->insertGetId($this->table, $data2);

                    $config['params']['doc'] = 'CONTACTINFOENTRY';
                    $this->logger->sbcmasterlog($line,   $config,  'CREATE CONTACTPERSON - LINE: ' . $line  . ' - NAME: ' . $data2['contact1']);
                } else {
                    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['editby'] = $config['params']['user'];
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'Saved all Successfully', 'data' => $returndata];
    } // end function  

    public function delete($config)
    {
        $row = $config['params']['row'];

        $qry = "delete from contacts where empid=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['empid'], $row['line']]);
        $config['params']['doc'] = 'CONTACTINFOENTRY';


        $this->logger->sbcmasterlog(
            $row['line'],
            $config,
            'DELETE CONTACTPERSON - LINE: ' . $row['line'] . ' - NAME: ' . $row['contact1']
        );

        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }


    private function loaddataperrecord($empid, $line, $config)
    {
        $aplid = $empid;

        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";

        $qry = "select " . $select . " from contacts where empid=? and line=?";

        $data = $this->coreFunctions->opentable($qry, [$aplid, $line]);
        return $data;
    }

    public function loaddata($config)
    {
        $aplid = $config['params']['tableid'];
        $center = $config['params']['center'];
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";


        $qry = "select " . $select . " from contacts where empid=? order by line";

        $data = $this->coreFunctions->opentable($qry, [$aplid]);
        return $data;
    }

    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'lookuplogs':
                return $this->lookuplogs($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
                break;
        }
    }

    public function lookuplogs($config)
    {
        $lookupsetup = array(
            'type' => 'show',
            'title' => 'List of Logs',
            'style' => 'width:1000px;max-width:1000px;'
        );

        $trno = $config['params']['tableid'];

        $cols = [
            ['name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;'],
        ];

        $doc = 'CONTACTINFOENTRY';


        $qry = "
      select trno, doc, task, dateid as dateid, dateid as sort, user, editby, editdate
      from " . $this->tablelogs . "
      where doc = ?
      order by sort desc
    ";

        $data = $this->coreFunctions->opentable($qry, [$doc, $doc]);

        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
    }
} //end class
