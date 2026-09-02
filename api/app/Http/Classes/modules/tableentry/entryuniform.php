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
use DateTime;

class entryuniform
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Uniform';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'cluniform';
    private $othersClass;
    public $style = 'width:100%;';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $fields = ['issued', 'description', 'rem'];
    public $showclosebtn = false;
    private $reporter;
    private $logger;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->reporter = new SBCPDF;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = ['load' => 0];
        return $attrib;
    }

    public function createTab($config)
    {
        $columns = ['action', 'issued', 'description', 'byear', 'rem'];
        $tab = [
            $this->gridname => [
                'gridcolumns' => $columns
            ]
        ];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $stockbuttons = ['save', 'delete'];

        $tab = [
            $this->gridname => [
                'gridcolumns' => $columns
            ]
        ];

        $obj = $this->tabClass->createTab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$issued]['type'] = 'date';
        $obj[0][$this->gridname]['columns'][$byear]['label'] = 'Cyyear';
        $obj[0][$this->gridname]['columns'][$rem]['label'] = 'Remarks';
        $obj[0][$this->gridname]['columns'][$rem]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:50px;whiteSpace: normal;min-width:50px;";


        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['addrecord', 'saveallentry', 'whlog'];

        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }

    public function loaddata($config)
    {
        $clientid = $config['params']['tableid'];
        $params = [$clientid];
        $filtersearch = " where clientid = ?";
        $searchfield  = $this->fields;

        if (isset($config['params']['filter'])) {
            $search = $config['params']['filter'];
            foreach ($searchfield as $sfield) {
                $filtersearch .= " and " . $sfield . " like ?";
                $params[] = '%' . $search . '%';
            }
        }

        $select = $this->selectqry() . ", '' as bgcolor";
        $qry    = "select " . $select . " from " . $this->table . $filtersearch . " order by line";

        $data = $this->coreFunctions->opentable($qry, $params);

        return $data;
    }

    private function selectqry()
    {
        $qry = "line, cyyear as byear";
        foreach ($this->fields as $key => $value) {
            $qry = $qry . ',' . $value;
        }
        return $qry;
    }

    public function add($config)
    {
        $data = [];
        $data['line'] = 0;
        $data['clientid'] = $config['params']['tableid'];
        $data['issued'] = $this->othersClass->getCurrentDate();
        $data['description'] = '';
        $data['byear'] = '';
        $data['rem'] = '';
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        $companyid = $config['params']['companyid'];
        $clientid = $config['params']['tableid'];
        $dateTables = ['cluniform'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

        $fieldsToSave = array_merge($this->fields, ['cyyear']);

        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($fieldsToSave as $key2 => $value2) {
                    if ($value2 == 'cyyear') {
                        $val = isset($data[$key]['byear']) ? $data[$key]['byear'] : '';
                    } else {
                        $val = $data[$key][$value2];
                    }
                    $data2[$value2] = $this->othersClass->sanitizekeyfieldFast($value2, $val, $lookups);
                }
                $data2['clientid'] = $clientid;

                if (empty(trim($data2['description']))) {
                    return ['status' => false, 'msg' => 'Saving failed. Please input description.'];
                }

                if ($data[$key]['line'] == 0) {
                    $line = $this->coreFunctions->insertGetId($this->table, $data2);
                    $config['params']['doc'] = 'ENTRYUNIFORM';
                    $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . $data2['description']);
                } else {
                    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['editby'] = $config['params']['user'];
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
                    $this->logger->sbcmasterlog($data[$key]['line'], $config, ' UPDATE - ' . $data2['description']);
                }
            }
        }
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
    }

    public function save($config)
    {
        $row = $config['params']['row'];
        $companyid = $config['params']['companyid'];
        $clientid = $config['params']['tableid'];
        $dateTables = ['cluniform'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

        $fieldsToSave = array_merge($this->fields, ['cyyear']);

        $data = [];
        foreach ($fieldsToSave as $key2 => $value) {
            if ($value == 'cyyear') {
                $val = isset($row['byear']) ? $row['byear'] : '';
            } else {
                $val = $row[$value];
            }
            $data[$value] = $this->othersClass->sanitizekeyfieldFast($value, $val, $lookups);
        }
        $data['clientid'] = $clientid;

        if (empty(trim($data['description']))) {
            return ['status' => false, 'msg' => 'Saving failed. Please input description.'];
        }

        if ($row['line'] == 0) { // insert
            $line = $this->coreFunctions->insertGetId($this->table, $data);

            if ($line != 0) {
                $config['params']['doc'] = 'ENTRYUNIFORM';
                $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . $data['description']);
                $returnrow = $this->loaddataperrecord($line);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else { // update
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            $update = $this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]);
            if ($update) {
                $returnrow = $this->loaddataperrecord($row['line']);
                $this->logger->sbcmasterlog($row['line'], $config, ' UPDATE - ' . $data['description']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Update failed.'];
            }
        }
    } // end function

    private function loaddataperrecord($line)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " where line=?";
        $data = $this->coreFunctions->opentable($qry, [$line]);
        // var_dump($data);
        return $data;
    }

    public function delete($config)
    {
        $row = $config['params']['row'];

        $data = $this->loaddataperrecord($row['line']);
        $qry = "select model as value from item where model=?";
        $count = $this->coreFunctions->datareader($qry, [$row['line']]);

        if ($count != '') {
            return ['clientid' => $row['line'], 'status' => false, 'msg' => 'Already have transaction...'];
        }
        $qry = "delete from " . $this->table . " where line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
        $config['params']['doc'] = 'ENTRYUNIFORM';
        $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE LINE: ' . $row['line'] . ' - ' . $row['description']);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }

    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'whlog':
                return $this->lookuplogs($config);
                break;

            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
                break;
        }
    }

    public function lookuplogs($config)
    {
        $doc = 'ENTRYUNIFORM';
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

        $trno = $config['params']['tableid'];

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
}
