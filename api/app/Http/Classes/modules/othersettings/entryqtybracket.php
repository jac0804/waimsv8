<?php

namespace App\Http\Classes\modules\othersettings;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class entryqtybracket
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Quantity Bracket Setup';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'qtybracket';
    private $othersClass;
    public $style = 'width:100%;';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $fields = ['name', 'minimum', 'maximum'];
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
        $attrib = array('load' => 2989, 'save' => 2989);
        return $attrib;
    }

    public function createTab($config)
    {
        $name = 0;
        $minimum = 1;
        $maximum = 2;
        $tab = [
            $this->gridname => [
                'gridcolumns' => ['name', 'minimum', 'maximum']
            ]
        ];

        $stockbuttons = ['save'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$name]['type'] = "label";
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['saveallentry', 'whlog'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function loaddata($config)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " order by line";
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }

    private function selectqry()
    {
        $qry = "line ";
        foreach ($this->fields as $key => $value) {
            $qry = $qry . ',' . $value;
        }
        return $qry;
    }

    private function loaddataperrecord($line)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " where line=?";
        $data = $this->coreFunctions->opentable($qry, [$line]);
        return $data;
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

                $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                $data2['editby'] = $config['params']['user'];

                $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
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
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];

        if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
            $returnrow = $this->loaddataperrecord($row['line']);
            return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
        } else {
            return ['status' => false, 'msg' => 'Saving failed.'];
        }
    } //end function

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
