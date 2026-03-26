<?php

namespace App\Http\Classes\modules\tableentry;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use Datetime;
use DateInterval;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\lookup\constructionlookup;
use App\Http\Classes\lookup\warehousinglookup;


class loanpurpose
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Loan Purpose';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'reqcategory';
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['reqtype', 'ispurpose', 'category'];
    public $showclosebtn = true;
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $logger;

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
        $tab = [$this->gridname => ['gridcolumns' => ['action', 'reqtype']]];

        $stockbuttons = ['save', 'delete'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][0]['style'] = 'width: 70px;whiteSpace: normal;min-width:10px;max-width:70px;';
        $obj[0][$this->gridname]['columns'][1]['style'] = 'width: 450px;whiteSpace: normal;min-width:10px;max-width:450px;';
        $obj[0][$this->gridname]['columns'][1]['label'] = 'Description';
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
        $data['category'] = $config['params']['sourcerow']['line'];
        $data['reqtype'] = '';
        $data['ispurpose'] = 1;
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
        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }

                $qry = "select line,category,reqtype from  reqcategory where line= '" . $data[$key]['line'] . "'  limit 1";
                $opendata = $this->coreFunctions->opentable($qry);
                $resultdata =  json_decode(json_encode($opendata), true);

                if ($data[$key]['line'] == 0) {
                    $line = $this->coreFunctions->insertGetId($this->table, $data2);
                    $config['params']['doc'] = 'LOANPURPOSE';
                    $this->logger->sbcmasterlog($data[$key]['category'], $config, ' CREATE - LINE : ' . $line . ' - ' . $data[$key]['reqtype'], 0, 1);
                } else {
                    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['editby'] = $config['params']['user'];
                    $config['params']['doc'] = 'LOANPURPOSE';
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
                    $this->logger->sbcmasterlog($data[$key]['category'], $config, ' UPDATE - LINE : ' . $data[$key]['line'] . ' - ' . $resultdata[0]['reqtype'] . ' => '  . $data[$key]['reqtype'], 0, 1);
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

        $qry = "select line,category,reqtype from  reqcategory where line= '" . $row['line'] . "'  limit 1";
        $opendata = $this->coreFunctions->opentable($qry);
        $resultdata =  json_decode(json_encode($opendata), true);

        if ($row['line'] == 0) {
            $line = $this->coreFunctions->insertGetId($this->table, $data);
            if ($line != 0) {
                $returnrow = $this->loaddataperrecord($line);
                $config['params']['doc'] = 'LOANPURPOSE';
                $this->logger->sbcmasterlog($row['category'], $config, ' CREATE - LINE : ' . $line . ' - ' . $row['reqtype'], 0, 1);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];

            if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
                $returnrow = $this->loaddataperrecord($row['line']);
                $config['params']['doc'] = 'LOANPURPOSE';
                $this->logger->sbcmasterlog($row['category'], $config, ' UPDATE - LINE : ' . $row['line'] . ' - ' . $resultdata[0]['reqtype'] . ' => ' . $row['reqtype'], 0, 1);
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
        $config['params']['doc'] = 'LOANPURPOSE';
        $this->logger->sbcdelmaster_log($row['category'], $config, 'REMOVE - LINE: ' . $row['line'] . ' - ' . $row['reqtype'], 1);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }


    private function loaddataperrecord($stockgrp_id)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " where line=?";
        $data = $this->coreFunctions->opentable($qry, [$stockgrp_id]);
        return $data;
    }

    public function loaddata($config)
    {
        $line = isset($config['params']['row']['line']) ? $config['params']['row']['line'] : $config['params']['sourcerow']['line'];
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " where category = ? and ispurpose=1  order by line";
        $data = $this->coreFunctions->opentable($qry, [$line]);

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
        $doc = strtoupper("LOANPURPOSE");
        $lookupsetup = array(
            'type' => 'show',
            'title' => 'List of Logs',
            'style' => 'width:1000px;max-width:1000px;'
        );

        // lookup columns
        $cols = array(
            array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

        );

        $trno = strtoupper($config['params']['sourcerow']['line']);

        $qry = "
        select trno, doc, task, log.user, dateid, 
        if(pic='','blank_user.png',pic) as pic
        from " . $this->tablelogs . " as log
        left join useraccess as u on u.username=log.user
        where log.doc = '" . $doc . "' and trno = $trno
        union all
        select trno, doc, task, log.user, dateid, 
        if(pic='','blank_user.png',pic) as pic
        from  " . $this->tablelogs_del . " as log
        left join useraccess as u on u.username=log.user
        where log.doc = '" . $doc . "' and trno = $trno ";

        $qry = $qry . " order by dateid desc";
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
    }
} //end class
