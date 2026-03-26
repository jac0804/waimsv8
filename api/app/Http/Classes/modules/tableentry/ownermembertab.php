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

class ownermembertab
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'OWNER MEMBER';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'contacts';
    private $othersClass;
    public $style = 'width:100%;';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $fields = ['ownertype', 'ownername', 'addr2', 'empid', 'isownermember'];
    public $showclosebtn = false;
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
            'load' => 0
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $tab = [
            $this->gridname => [
                'gridcolumns' => ['action',  'ownertype', 'ownername', 'addr2']
            ]
        ];

        $stockbuttons = ['save', 'delete'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][0]['style'] = "width:50px;whiteSpace: normal;min-width:50px;";
        $obj[0][$this->gridname]['columns'][1]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][2]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][3]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
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
        $data['ownername'] = '';
        $data['ownertype'] = '';
        $data['addr2'] = '';
        $data['empid'] = $config['params']['tableid'];
        $data['isownermember'] = 1;
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    private function selectqry()
    {
        $qry = "line,isownermember";
        foreach ($this->fields as $key => $value) {
            $qry = $qry . ',' . $value;
        }
        return $qry;
    }

    public function saveallentry($config)
    {
        $trno = $config['params']['tableid'];
        $data = $config['params']['data'];

        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }
                if ($data[$key]['line'] == 0 && $data[$key]['ownername'] != '') {
                    $qry = "select ownername from contacts where ownername = '" . $data[$key]['ownername'] . "' limit 1";
                    $opendata = $this->coreFunctions->opentable($qry);
                    $resultdata =  json_decode(json_encode($opendata), true);
                    if (!empty($resultdata[0]['ownername'])) {
                        if (trim($resultdata[0]['ownername']) == trim($data[$key]['ownername'])) {
                            return ['status' => false, 'msg' => ' Owner name ( ' . $resultdata[0]['ownername'] . ' )' . ' is already exist', 'data' => [$resultdata]];
                        }
                    }
                }
                if (trim($data[$key]['ownername'] == '')) {
                    return ['status' => false, 'msg' => 'Owner name is empty.'];
                }
                if ($data[$key]['line'] == 0) {
                    $line  = $this->coreFunctions->insertGetId($this->table, $data2);
                    $config['params']['doc'] = 'ENTRYHOUSEHOLDD';
                    $this->logger->sbcmasterlog($line, $config, ' CREATE  - Owner Name: ' . $data[$key]['ownername']
                        . ' Owner type: ' . $data[$key]['ownertype']
                        . ' Owner Address: ' . $data[$key]['addr2']);
                } else {

                    if ($data[$key]['line'] != 0 && $data[$key]['ownername'] != '') {
                        $qry = "select ownername,line from contacts where ownername = '" . $data[$key]['ownername'] . "' limit 1";
                        $opendata = $this->coreFunctions->opentable($qry);
                        $resultdata =  json_decode(json_encode($opendata), true);
                        if (!empty($resultdata[0]['ownername'])) {
                            if (trim($resultdata[0]['ownername']) == trim($data[$key]['ownername'])) {
                                if ($data[$key]['line'] == $resultdata[0]['line']) {
                                    goto update;
                                }
                                return ['status' => false, 'msg' => ' Owner name ( ' . $resultdata[0]['ownername'] . ' )' . ' is already exist.', 'data' => [$resultdata], 'rowid' => [$data[$key]['line']  . ' -- ' . $resultdata[0]['line']]];
                            } else {
                                update:
                                $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                                $data2['editby'] = $config['params']['user'];
                                $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
                                $this->logger->sbcmasterlog($data[$key]['line'], $config, ' UPDATE - ' . $data[$key]['ownername']);
                            }
                        } else {
                            goto update;
                        }
                    } //end if
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $returndata];
    } // end function

    public function save($config)
    {
        $data = [];
        $row = $config['params']['row'];
        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }
        if ($row['line'] == 0 && $row['ownername'] != '') {
            $qry = "select ownername from contacts where ownername = '" . $row['ownername'] . "' limit 1";
            $opendata = $this->coreFunctions->opentable($qry);
            $resultdata =  json_decode(json_encode($opendata), true);
            if (!empty($resultdata[0]['ownername'])) {
                if (trim($resultdata[0]['ownername']) == trim($row['ownername'])) {
                    return ['status' => false, 'msg' => ' Owner Name ( ' . $resultdata[0]['ownername'] . ' )' . ' is already exist', 'data' => [$resultdata]];
                }
            }
        }
        if (trim($row['ownername'] == '')) {
            return ['status' => false, 'msg' => 'Owner name is empty.'];
        }
        if ($row['line'] == 0) {
            $line = $this->coreFunctions->insertGetId($this->table, $data);
            if ($line != 0) {
                $returnrow = $this->loaddataperrecord($line);
                $config['params']['doc'] = 'ENTRYHOUSEHOLDD';
                $this->logger->sbcmasterlog($line, $config, ' CREATE - Owner Name: ' . $data['ownername']
                    . ' Owner type: ' . $data['ownertype']
                    . ' Owner Address: ' . $data['addr2']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {

            if ($row['line'] != 0 && $row['ownername'] != '') {
                $qry = "select ownername,line from contacts where ownername = '" . $row['ownername'] . "' limit 1";
                $opendata = $this->coreFunctions->opentable($qry);
                $resultdata =  json_decode(json_encode($opendata), true);
                if (!empty($resultdata[0]['ownername'])) {
                    if (trim($resultdata[0]['ownername']) == trim($row['ownername'])) {
                        if ($row['line'] == $resultdata[0]['line']) {
                            goto update;
                        }
                        return ['status' => false, 'msg' => ' Owner name ( ' . $resultdata[0]['ownername'] . ' )' . ' is already exist', 'data' => [$resultdata], 'rowid' => [$row['line']  . ' -- ' . $resultdata[0]['line']]];
                    } else {
                        update:
                        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
                        $data['editby'] = $config['params']['user'];
                        if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
                            $returnrow = $this->loaddataperrecord($row['line']);
                            $this->logger->sbcmasterlog($row['line'], $config, ' UPDATE - ' . $data['ownername']);
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
        $qry = "delete from " . $this->table . " where line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
        $config['params']['doc'] = 'ENTRYHOUSEHOLDD';
        $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE - ' . $row['ownername']);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }


    private function loaddataperrecord($line)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " where isownermember=1 and line=?";
        $data = $this->coreFunctions->opentable($qry, [$line]);
        return $data;
    }

    public function loaddata($config)
    {
        $tableid = $config['params']['tableid'];
        $filtersearch = "";
        $searcfield = $this->fields;
        $limit = "1000";

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
        $qry = "select " . $select . " from " . $this->table . " where isownermember=1  and empid = $tableid " . $filtersearch . " order by line limit " . $limit;
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
        $doc = strtoupper("ENTRYHOUSEHOLDD");
        $lookupsetup = array(
            'type' => 'show',
            'title' => 'Owner Members Logs',
            'style' => 'width:1000px;max-width:1000px;'
        );

        // lookup columns
        $cols = array(
            array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

        );
        $qry = "
            select trno, doc, task, log.user, dateid, 
            if(pic='','blank_user.png',pic) as pic
            from " . $this->tablelogs . " as log
            left join useraccess as u on u.username=log.user
             left join contacts as cn on cn.line=log.trno 
            where log.doc = '" . $doc . "' and  cn.isownermember=1
            union all
            select trno, doc, task, log.user, dateid, 
            if(pic='','blank_user.png',pic) as pic
            from  " . $this->tablelogs_del . " as log
            left join useraccess as u on u.username=log.user
             left join contacts as cn on cn.line=log.trno 
            where log.doc = '" . $doc . "' and  cn.isownermember=1 ";
        // var_dump($qry);
        $qry = $qry . " order by dateid desc";
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
    }
} //end class
