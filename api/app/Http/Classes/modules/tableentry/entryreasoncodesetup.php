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
use App\Http\Classes\modules\inventory\va;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class entryreasoncodesetup
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'Reason Code Setup';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'reqcategory';
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['code', 'description'];
    public $showclosebtn = false;
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $reporter;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->reporter = new SBCPDF;
        $this->logger = new Logger;
    } // end function

    public function getAttrib()
    {
        $attrib = array(
            'load' => 5025,
            'save' => 5025,
        );
        return $attrib;
    } // end function

    public function createTab($config)
    {
        $action = 0;
        $code = 1;
        $description = 2;

        $tab = [$this->gridname => ['gridcolumns' => ['action', 'code', 'description']]];

        $stockbuttons = ['save', 'delete'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:10%;whiteSpace: normal;min-width:100%;";
        $obj[0][$this->gridname]['columns'][$code]['style'] = "width:20%;whiteSpace: normal;min-width:100%;";
        $obj[0][$this->gridname]['columns'][$description]['style'] = "width:70%;whiteSpace: normal;min-width:100%;";
        
        if ($config['params']['companyid']==10){
            $obj[0][$this->gridname]['columns'][$code]['type'] = "coldel";
            $this->modulename = "Reason Setup";
        }
        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    } // end function

    public function createtabbutton($config)
    {
        $tbuttons = ['addrecord', 'saveallentry', 'whlog'];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    } // end function

    public function add($config)
    {
        $data = [];
        $data['line'] = 0;
        $data['description'] = '';
        $data['isreasoncode'] = 1;
        $data['code'] = '';
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    } // end function

    private function selectqry()
    {
        $qry = "line,code,description,isreasoncode";
        return $qry;
    } // end function

    public function saveallentry($config)
    {
        $companyid = $config['params']['companyid'];
        $data = $config['params']['data'];
        $data2 = [];
        foreach ($data as $key => $value) {

            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }
                $data2['isreasoncode'] = $this->othersClass->sanitizekeyfield('isreasoncode', $data[$key]['isreasoncode']);
                $data2['isloantype'] = 0;
                if ($data[$key]['line'] == 0 && $data[$key]['description'] != '') {
                    $qry = "select description from reqcategory where description = '" . $data[$key]['description'] . "' limit 1";
                    $opendata = $this->coreFunctions->opentable($qry);
                    $resultdata = json_decode(json_encode($opendata), true);
                    if (!empty($resultdata[0]['description'])) {
                        if (trim($resultdata[0]['description']) == trim($data[$key]['description'])) {
                            return ['status' => false, 'msg' => ' Description ( ' . $resultdata[0]['description'] . ' )' . ' is already exist', 'data' => [$resultdata]];
                        }
                    }
                }

                if($companyid !=10){
                    if (trim($data[$key]['code']) == '' && trim($data[$key]['description']) == '') {
                        return ['status' => false, 'msg' => 'Code & Description are empty'];
                    }
                    if (trim($data[$key]['code']) == '') {
                        return ['status' => false, 'msg' => 'Code is empty'];
                    }
                }
                
                if (trim($data[$key]['description']) == '') {
                    return ['status' => false, 'msg' => 'Description is empty'];
                }

                if ($data[$key]['line'] == 0) {
                    $line = $this->coreFunctions->insertGetId($this->table, $data2);
                    $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . $data[$key]['description']);
                } else {
                    if ($data[$key]['line'] != 0 && $data[$key]['description'] != '') {
                        $qry = "select description,line from reqcategory where description = '" . $data[$key]['description'] . "' limit 1";
                        $opendata = $this->coreFunctions->opentable($qry);
                        $resultdata = json_decode(json_encode($opendata), true);
                        if (!empty($resultdata[0]['description'])) {
                            if (trim($resultdata[0]['description']) == trim($data[$key]['description'])) {
                                if ($data[$key]['line'] == $resultdata[0]['line']) {
                                    goto update;
                                }
                                return ['status' => false, 'msg' => ' Name ( ' . $resultdata[0]['description'] . ' )' . ' is already exist', 'data' => [$resultdata], 'rowid' => [$data[$key]['line']  . ' -- ' . $resultdata[0]['line']]];
                            } else {
                                update:
                                $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                                $data2['editby'] = $config['params']['user'];
                                $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
                                $this->logger->sbcmasterlog($data[$key]['line'], $config, ' UPDATE - ' . $data[$key]['description']);
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
        $companyid = $config['params']['companyid'];
        $row = $config['params']['row'];
        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }

        $data['isreasoncode'] = 1;
        $data['isloantype'] = 0;

        if ($row['line'] == 0 && $row['description'] != '') {
            $qry = "select description from reqcategory where description = '" . $row['description'] . "' limit 1";
            $opendata = $this->coreFunctions->opentable($qry);
            $resultdata = json_decode(json_encode($opendata), true);
            if (!empty($resultdata[0]['description']) && trim($resultdata[0]['description']) == trim($row['description'])) {
                return ['status' => false, 'msg' => ' Description ( ' . $resultdata[0]['description'] . ' )' . ' is already exist', 'data' => [$resultdata]];
            }
        }

        if($companyid !=10){
            if (trim($data['code']) == '' && trim($data['description']) == '') {
                return ['status' => false, 'msg' => 'Code & Description are empty'];
            }
            if (trim($data['code']) == '') {
                return ['status' => false, 'msg' => 'Code is empty'];
            }
        }
        
        if (trim($data['description']) == '') {
            return ['status' => false, 'msg' => 'Description is empty'];
        }

        if ($row['line'] == 0) {
            $line = $this->coreFunctions->insertGetId($this->table, $data);
            if ($line != 0) {
                $returnrow = $this->loaddataperrecord($line);
                $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . $data['description']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {
            if ($row['description'] != '') {
                $qry = "select line,description from reqcategory where description = '" . $row['description'] . "' limit 1";
                $opendata = $this->coreFunctions->opentable($qry);
                $resultdata = json_decode(json_encode($opendata), true);
                if (!empty($resultdata[0]['description'])) {
                    if (trim($resultdata[0]['description']) == trim($row['description'])) {
                        if ($row['line'] == $resultdata[0]['line']) {
                            return $this->updateRecord($data, $row, $config);
                        }
                        return ['status' => false, 'msg' => ' Description ( ' . $resultdata[0]['description'] . ' )' . ' is already exist', 'data' => [$resultdata], 'rowid' => [$row['line'] . ' -- ' . $resultdata[0]['line']]];
                    } else {
                        return $this->updateRecord($data, $row, $config);
                    }
                } else {
                    return $this->updateRecord($data, $row, $config);
                }
            }
        }
    } // end function

    private function updateRecord($data, $row, $config)
    {
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];

        if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
            $returnrow = $this->loaddataperrecord($row['line']);
            $this->logger->sbcmasterlog($row['line'], $config, ' UPDATE - ' . $data['description']);
            return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
        } else {
            return ['status' => false, 'msg' => 'Saving failed.'];
        }
    } // end function

    private function loaddataperrecord($line)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " where line=?";
        $data = $this->coreFunctions->opentable($qry, [$line]);
        return $data;
    } // end function

    public function delete($config)
    {
        $row = $config['params']['row'];
        $qry = "delete from " . $this->table . " where line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    } // end function

    public function loaddata($config)
    {
        $select = $this->selectqry();
        $select = $select . ", '' as bgcolor ";

        $qry = "select " . $select . " from " . $this->table . " 
        where isreasoncode <> 0
        order by line ";
        $data = $this->coreFunctions->opentable($qry);

        return $data;
    } // end function

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
    } // end function

    public function lookuplogs($config)
    {
        $doc = $config['params']['doc'];
        $lookupsetup = array(
            'type' => 'show',
            'title' => 'Reason Code Logs',
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
    } // end function
} //end class
