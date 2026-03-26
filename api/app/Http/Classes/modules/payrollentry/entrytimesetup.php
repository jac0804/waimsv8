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
use App\Http\Classes\sbcscript\sbcscript;

class entrytimesetup
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Time Setup';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'timesetup';
    private $othersClass;
    public $style = 'width:100%;';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $fields = ['times'];
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
        $attrib = ['load' => 5552];
        return $attrib;
    }

    public function createTab($config)
    {
        $columns = ['action',  'times', 'order'];
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
        $obj[0][$this->gridname]['columns'][$times]['type'] = 'time';
        $obj[0][$this->gridname]['columns'][$order]['type'] = 'hidden';
        $obj[0][$this->gridname]['columns'][$order]['label'] = '';
        $obj[0][$this->gridname]['columns'][$times]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$order]['style'] = "width:500px;whiteSpace: normal;min-width:500px;";

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
        $filtersearch = "";
        $searchfield  = $this->fields;

        if (isset($config['params']['filter'])) {
            $search = $config['params']['filter'];
            foreach ($searchfield as $sfield) {
                if ($filtersearch == "") {
                    $filtersearch .= " and (" . $sfield . " like '%" . $search . "%'";
                } else {
                    $filtersearch .= " or " . $sfield . " like '%" . $search . "%'";
                }
            }
            $filtersearch .= ")";
        }

        $select = $this->selectqry() . ", '' as bgcolor";
        $qry    = "select " . $select . " from " . $this->table .
            " where 1=1 " . $filtersearch . " order by line";

        $data = $this->coreFunctions->opentable($qry);





        return $data;
    }



    private function selectqry()
    {
        $qry = "line";

        foreach ($this->fields as $value) {
            if ($value === 'times') {

                $qry .= ", DATE_FORMAT(times, '%H:%i') AS times";
            } else {
                $qry .= ", " . $value;
            }
        }

        return $qry;
    }



    public function add($config)
    {
        $data = [];
        $data['line'] = 0;
        $data['times'] = '';
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    public function saveallentry($config)
    {
        $data = $config['params']['data'];

        foreach ($data as $key => $value) {
            $data2 = [];

            if (!empty($data[$key]['bgcolor'])) {
                foreach ($this->fields as $field) {
                    if ($field === 'line' || $field === 'bgcolor') {
                        continue;
                    }

                    if (isset($data[$key][$field])) {
                        if ($field === 'times') {
                            $timeValue = $data[$key][$field];
                            $currentDate = date('Y-m-d');

                            if (strtotime($timeValue) !== false && strlen($timeValue) > 5) {
                                $timeOnly = date('H:i:s', strtotime($timeValue));
                                $timeValue = $currentDate . ' ' . $timeOnly;
                            } else {
                                $timeValue = $currentDate . ' ' . $timeValue . ':00';
                            }

                            $data2[$field] = $this->othersClass->sanitizekeyfield($field, $timeValue);
                        } else {
                            $data2[$field] = $this->othersClass->sanitizekeyfield($field, $data[$key][$field]);
                        }
                    }
                }

                $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                $data2['editby'] = $config['params']['user'];

                if (isset($data[$key]['line']) && $data[$key]['line'] == 0) {
                    $line = $this->coreFunctions->insertGetId($this->table, $data2);
                    $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . $data[$key]['times']);
                } else {
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
                    $this->logger->sbcmasterlog($data[$key]['line'], $config, ' UPDATE - ' . 'LINE: ' . $data[$key]['line'] . '-' . $data[$key]['times']);
                }
            }
        }

        $returndata = $this->loaddata($config);

        return [
            'status' => true,
            'msg' => 'All saved successfully.',
            'data' => $returndata
        ];
    }


    public function save($config)
    {
        $row = $config['params']['row'];
        $data = [];

        foreach ($this->fields as $field) {
            if (isset($row[$field])) {
                if ($field === 'times') {
                    $timeValue = $row[$field];
                    $currentDate = date('Y-m-d');

                    if (strtotime($timeValue) !== false && strlen($timeValue) > 5) {
                        $timeOnly = date('H:i:s', strtotime($timeValue));
                        $timeValue = $currentDate . ' ' . $timeOnly;
                    } else {
                        $timeValue = $currentDate . ' ' . $timeValue . ':00';
                    }

                    $data[$field] = $this->othersClass->sanitizekeyfield($field, $timeValue);
                } else {
                    $data[$field] = $this->othersClass->sanitizekeyfield($field, $row[$field]);
                }
            }
        }

        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];

        if ($row['line'] == 0) {
            unset($data['line']);
            $line = $this->coreFunctions->insertGetId($this->table, $data);

            if ($line != 0) {
                $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . $data['times']);
                $returnrow = $this->loaddataperrecord($line);

                if (!empty($returnrow['times'])) {
                    $returnrow['times'] = date('H:i', strtotime($returnrow['times']));
                }

                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {
            $update = $this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]);
            if ($update) {
                $returnrow = $this->loaddataperrecord($row['line']);

                if (!empty($returnrow['times'])) {
                    $returnrow['times'] = date('H:i', strtotime($returnrow['times']));
                }

                $this->logger->sbcmasterlog($row['line'], $config, ' UPDATE - ' . 'LINE: ' . $row['line'] . '-'  . $row['times']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Update failed.'];
            }
        }
    }




    private function loaddataperrecord($line)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " where line=?";
        $data = $this->coreFunctions->opentable($qry, [$line]);
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
        $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE - ' . $row['times']);
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
