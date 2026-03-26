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

class entrypurposeofpayment
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'Purpose of Payment';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'reqcategory';
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['category',  'inactive','ispaytype'];
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
            'load' => 5039,
            'save' => 5039,
        );
        return $attrib;
    } // end function

    public function createTab($config)
    {
        $columns = ['action', 'category',  'inactive'];
        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $columns]];

        $stockbuttons = ['save', 'delete'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:10%;whiteSpace: normal;min-width:100%;";
        $obj[0][$this->gridname]['columns'][$category]['style'] = "width:20%;whiteSpace: normal;min-width:100%;";
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
        $data['ispaytype'] = 1;
        $data['category'] = '';
        $data['inactive'] = 'false';
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    } // end function

    private function selectqry()
    {
        $qry = "line,category,reqtype,ispaytype,(case when inactive=0 then 'false' else 'true' end) as inactive";
        return $qry;
    } // end function

    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        $data2 = [];
        foreach ($data as $key => $value) {

            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }
                
                $data2['ispaytype'] = $this->othersClass->sanitizekeyfield('ispaytype', $data[$key]['ispaytype']);
                $data2['inactive'] = $this->othersClass->sanitizekeyfield('inactive', $data[$key]['inactive']);

                if ($data[$key]['line'] == 0 && $data[$key]['category'] != '') {
                    $qry = "select category from reqcategory where category = '" . $data[$key]['category'] . "' and ispaytype = 1 limit 1";
                    $opendata = $this->coreFunctions->opentable($qry);
                    $resultdata = json_decode(json_encode($opendata), true);
                    if (!empty($resultdata[0]['category'])) {
                        if (trim($resultdata[0]['category']) == trim($data[$key]['category'])) {
                            return ['status' => false, 'msg' =>  $resultdata[0]['category'] .  ' is already exist', 'data' => [$resultdata]];
                        }
                    }
                }

                if (trim($data[$key]['category']) == '') {
                    return ['status' => false, 'msg' => 'Purpose is empty'];
                }

                if ($data[$key]['line'] == 0) {
                    $line = $this->coreFunctions->insertGetId($this->table, $data2);
                    $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . $data[$key]['category']);
                } else {
                    if ($data[$key]['category'] != '') {
                        $qry = "select reqtype,line from reqcategory where category = '" . $data[$key]['category'] . "' and line <> ".$data[$key]['line'] ." and ispaytype = 1 limit 1";
                        $opendata = $this->coreFunctions->opentable($qry);
                        $resultdata = json_decode(json_encode($opendata), true);
                        if (!empty($resultdata)) {
                            return ['status' => false, 'msg' => $resultdata[0]['category'] . ' already exist', 'data' => [$resultdata], 'rowid' => [$data[$key]['line']  . ' -- ' . $resultdata[0]['line']]];
                        } else {
                            update:
                                $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                                $data2['editby'] = $config['params']['user'];
                                $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
                                $this->logger->sbcmasterlog($data[$key]['line'], $config, ' UPDATE - ' . $data[$key]['category']);
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
        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }

        $data['inactive'] = $this->othersClass->sanitizekeyfield('inactive', $row['inactive']);

        if ($row['line'] == 0 && $row['category'] != '') {
            $qry = "select category from reqcategory where category = '" . $row['category'] . "' and ispaytype = 1 limit 1";
            $opendata = $this->coreFunctions->opentable($qry);
            $resultdata = json_decode(json_encode($opendata), true);
            if (!empty($resultdata[0]['category']) && trim($resultdata[0]['category']) == trim($row['category'])) {
                return ['status' => false, 'msg' => $resultdata[0]['category'] . ' is already exist', 'data' => [$resultdata]];
            }
        }

       
        if (trim($data['category']) == '') {
            return ['status' => false, 'msg' => 'Purpose is empty'];
        }

        if ($row['line'] == 0) {
            $line = $this->coreFunctions->insertGetId($this->table, $data);
            if ($line != 0) {
                $returnrow = $this->loaddataperrecord($line);
                $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . $data['category']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {
            if ($row['category'] != '') {
                $qry = "select line,category,case when inactive=0 then 'false' else 'true' end as inactive from  reqcategory where category = '" . $row['category'] . "' and line <> ".$row['line']." and ispaytype=1 limit 1";
                $opendata = $this->coreFunctions->opentable($qry);
                $resultdata =  json_decode(json_encode($opendata), true);

                if (!empty($resultdata)) {
                    return ['status' => false, 'msg' =>  $resultdata[0]['category'] . ' already exist', 'data' => [$resultdata], 'rowid' => [$row['line']  . ' -- ' . $resultdata[0]['line']]];
                } else {
                    update:
                        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
                        $data['editby'] = $config['params']['user'];
                        if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
                            $returnrow = $this->loaddataperrecord($row['line']);
                            $this->logger->sbcmasterlog($row['line'], $config, ' UPDATE - ' . $data['category']);
                            return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
                        } else {
                            return ['status' => false, 'msg' => 'Saving failed.'];
                        }
                }
            }
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
        where ispaytype <> 0
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
            'title' => 'Purpose of Payment Logs',
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
