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

class entryexpiration
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'EXPIRATION';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'expiration';
    private $othersClass;
    public $style = 'width:100%;';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $fields = ['expiry', 'days'];
    public $showclosebtn = false;
    public $showsearch = false;
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
        $attrib = array(
            'load' => 5118
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $column = ['action', 'days', 'expiry'];

        foreach ($column as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $column]];
        $stockbuttons = ['save', 'delete'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$expiry]['type'] = "input";
        $obj[0][$this->gridname]['columns'][$expiry]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$expiry]['label'] = 'Days';
        $obj[0][$this->gridname]['columns'][$expiry]['readonly'] = false;
        $obj[0][$this->gridname]['columns'][$days]['label'] = 'Expiration';
        $obj[0][$this->gridname]['columns'][$expiry]['align'] = 'text-right';

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
        $data['expiry'] = 0;
        $data['days'] = '';
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    private function selectqry()
    {
        $qry = "line";
        foreach ($this->fields as $key => $value) {
            $qry = $qry . ',' . $value;
        }
        return $qry;
    }

    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        $companyid = $config['params']['companyid'];
        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }
                if ($data[$key]['line'] == 0 && $data[$key]['days'] != '') {
                    $qry = "select days from expiration where days = '" . $data[$key]['days'] . "' limit 1";
                    $opendata = $this->coreFunctions->opentable($qry);
                    $resultdata =  json_decode(json_encode($opendata), true);
                    if (!empty($resultdata[0]['days'])) {
                        if (trim($resultdata[0]['days']) == trim($data[$key]['days'])) {
                            return ['status' => false, 'msg' => ' Expiration ( ' . $resultdata[0]['days'] . ' )' . ' is already exist', 'data' => [$resultdata]];
                        }
                    }
                }
                if (trim($data[$key]['days'] == '')) {
                    return ['status' => false, 'msg' => 'Expiration is empty'];
                }

                if ($data[$key]['line'] == 0) {
                    $id = $this->coreFunctions->insertGetId($this->table, $data2);
                    $this->logger->sbcmasterlog($id, $config, ' CREATE - ' . $data[$key]['days']);
                } else {
                    if ($data[$key]['line'] != 0 && $data[$key]['days'] != '') {
                        $qry = "select days,line,expiry from expiration where days = '" . $data[$key]['days'] . "' limit 1";
                        $opendata = $this->coreFunctions->opentable($qry);
                        $resultdata =  json_decode(json_encode($opendata), true);
                        if (!empty($resultdata[0]['days'])) {
                            if (trim($resultdata[0]['days']) == trim($data[$key]['days'])) {
                                if ($data[$key]['line'] == $resultdata[0]['line']) {
                                    goto update;
                                }
                                return ['status' => false, 'msg' => ' Expiration ( ' . $resultdata[0]['days'] . ' )' . ' is already exist', 'data' => [$resultdata], 'rowid' => [$data[$key]['line']  . ' -- ' . $resultdata[0]['line']]];
                            } else {
                                update:

                                if ($resultdata[0]['days'] != $data[$key]['expiry']) {
                                    $count = $this->checkusedexpiry($data[$key]['line']);
                                    if ($count != 0) {
                                        return ['status' => false, 'msg' => "Can't be Change Already used."];
                                    }
                                }
                                $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                                $data2['editby'] = $config['params']['user'];
                                $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
                                $this->logger->sbcmasterlog($data[$key]['line'], $config, ' UPDATE - ' . $data[$key]['days']);
                            }
                        } else {
                            goto update;
                        }
                    }
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
        $companyid = $config['params']['companyid'];

        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }
        if ($row['line'] == 0 && $row['days'] != '') {
            $qry = "select days from expiration where days = '" . $row['days'] . "' limit 1";
            $opendata = $this->coreFunctions->opentable($qry);
            $resultdata =  json_decode(json_encode($opendata), true);
            if (!empty($resultdata[0]['days'])) {
                if (trim($resultdata[0]['days']) == trim($row['days'])) {
                    return ['status' => false, 'msg' => ' Expiration ( ' . $resultdata[0]['days'] . ' )' . ' is already exist', 'data' => [$resultdata]];
                }
            }
        }

        if (trim($data['days'] == '')) {
            return ['status' => false, 'msg' => 'Expiration is empty'];
        }
        if ($row['line'] == 0) {
            $line = $this->coreFunctions->insertGetId($this->table, $data);
            if ($line != 0) {
                $returnrow = $this->loaddataperrecord($line);
                $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . $data['days']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {

            if ($row['line'] != 0 && $row['days'] != '') {
                $qry = "select days,line,expiry from expiration where days = '" . $row['days'] . "' limit 1";
                $opendata = $this->coreFunctions->opentable($qry);
                $resultdata =  json_decode(json_encode($opendata), true);
                if (!empty($resultdata[0]['days'])) {
                    if (trim($resultdata[0]['days']) == trim($row['days'])) {
                        if ($row['line'] == $resultdata[0]['line']) {
                            goto update;
                        }
                        return ['status' => false, 'msg' => ' Expiration ( ' . $resultdata[0]['days'] . ' )' . ' is already exist', 'data' => [$resultdata], 'rowid' => [$row['line'] . ' -- ' . $resultdata[0]['line']]];
                    } else {

                        update:
                        if ($resultdata[0]['expiry'] != $row['expiry']) {
                            $count = $this->checkusedexpiry($row['line']);
                            if ($count != 0) {
                                return ['status' => false, 'msg' => "Can't be Change Already used."];
                            }
                        }
                        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
                        $data['editby'] = $config['params']['user'];

                        if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
                            $returnrow = $this->loaddataperrecord($row['line']);
                            $this->logger->sbcmasterlog($row['line'], $config, ' UPDATE - ' . $data['days']);
                            return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
                        } else {
                            return ['status' => false, 'msg' => 'Saving failed.'];
                        }
                    }
                } else {
                    goto update;
                }
            }
        }
    } //end function

    public function delete($config)
    {
        $row = $config['params']['row'];

        $count = $this->checkusedexpiry($row['line']);
        if ($count != 0) {
            return ['status' => false, 'msg' => "Can't be Change Already used."];
        }
        $qry = "delete from " . $this->table . " where line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
        $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE - ' . $row['days']);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }


    private function loaddataperrecord($line)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " where line=?";
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
        $searcfield = $this->fields;
        $search = '';
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

        if ($search != "") {
            $l = '';
        } else {
            $l = $limit;
        }
        $qry = "select " . $select . " from " . $this->table . " where 1=1 " . $filtersearch . " order by line $l";
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
            'title' => 'Item Category Master Logs',
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
    public function checkusedexpiry($line)
    {
        $qry = "select sum(exp) as value from (
                select count(expiryid) as exp from pohead where expiryid= $line
                union all
                select count(expiryid) as exp from hpohead where expiryid= $line
        
        ) as c";
        return $this->coreFunctions->datareader($qry);
    }
} //end class
