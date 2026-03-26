<?php

namespace App\Http\Classes\modules\tableentry;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

use Datetime;

class jobdone
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;
    private $logger;

    public $modulename = 'COMPLIANTS/ASSESSMENT';
    public $gridname = 'inventory';
    private $fields = ['jotrno', 'joline', 'empid', 'rem'];
    public $tablenum = 'transnum';
    private $table = 'headprrem';
    private $stock = 'stockinfotrans';
    private $hstock = 'hstockinfotrans';

    public $tablelogs = 'table_log';

    public $style = 'width:100%;max-width:70%;';
    public $issearchshow = true;
    public $showclosebtn = true;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->coreFunctions = new coreFunctions;
        $this->companysetup = new companysetup;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createHeadField($config)
    {
        $fields = [];
        $col1 = $this->fieldClass->create($fields);
        return array('col1' => $col1);
    }



    public function createTab($config)
    {

        $config['params']['trno'] = $config['params']['tableid'];
        $isposted = $this->othersClass->isposted($config);

        $stockbuttons = ['save', 'delete'];
        $columns = ['action', 'client', 'clientname', 'rem'];
        foreach ($columns as $key => $value) {
            $$value = $key;
        }
        $tab = [
            $this->gridname => [
                'gridcolumns' => $columns
            ]
        ];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$action]['style'] = 'width:10%;whiteSpace: normal;min-width:100%;';
        $obj[0][$this->gridname]['columns'][$clientname]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$client]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$client]['label'] = 'Employee Code';
        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Name';
        $obj[0][$this->gridname]['columns'][$rem]['label'] = 'Action/Job Done';
        if ($isposted) {
            $obj[0][$this->gridname]['columns'][$rem]['readonly'] = true;
        }

        return $obj;
    }

    public function createtabbutton($config)
    {
        $config['params']['trno'] = $config['params']['tableid'];
        $isposted = $this->othersClass->isposted($config);
        $tbuttons = ['additem', 'saveallentry'];
        if ($isposted) {
            $tbuttons = [];
        }
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[0]['action'] = "lookupsetup";
        $obj[0]['icon'] = "person_add";
        $obj[0]['label'] = "ADD EMPLOYEE";
        return $obj;
    }
    public function delete($config)
    {
        $config['params']['trno'] = $config['params']['tableid'];
        $isposted = $this->othersClass->isposted($config);
        $row = $config['params']['row'];
        $usetable = $this->table;
        $qry2 = "delete from " . $usetable . " where joline=? and jotrno = ?";
        $this->coreFunctions->execqry($qry2, 'delete', [$row['joline'], $row['jotrno']]);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }
    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        $config['params']['trno'] = $config['params']['tableid'];
        $data2 = [];
        foreach ($data as $key => $value) {
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }
                if ($data[$key]['rem'] == '') {
                    return ['status' => false, 'msg' => 'Action/Job Done is empty', 'data' => []];
                }
                if ($data[$key]['joline'] == 0) {
                    $joline = $this->coreFunctions->datareader("select ifnull(count(joline),0)+1 as value from headprrem where jotrno=? and trno = 0", [$config['params']['trno']]);
                    $data2['joline'] = $joline;
                    $data2['createdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['createby'] = $config['params']['user'];

                    $this->coreFunctions->sbcinsert($this->table, $data2);
                    $this->logger->sbcwritelog($config['params']['trno'], $config, 'Action/Job Done', 'CREATE - Description: ' . $data[$key]['rem']);
                } else {
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['joline' => $data[$key]['joline'], 'jotrno' => $data[$key]['jotrno'], 'empid' => $data[$key]['empid']]);
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $returndata, 'reloadhead' => true];
    } // end function  
    public function save($config)
    {
        $data = [];
        $row = $config['params']['row'];
        $config['params']['trno'] = $config['params']['tableid'];
        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }
        if ($row['bgcolor'] != '') {
            if ($row['rem'] == '') {
                return ['status' => false, 'msg' => 'Action/Job Done is empty', 'data' => []];
            }
            if ($row['joline'] == 0) {
                $joline = $this->coreFunctions->datareader("select ifnull(count(joline),0)+1 as value from headprrem where jotrno=? and trno = 0", [$config['params']['trno']]);
                $data['joline'] = $joline;
                $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
                $data['createby'] = $config['params']['user'];
                $this->coreFunctions->sbcinsert($this->table, $data);

                $returnrow = $this->loaddataperrecord($config, $joline);
                $this->logger->sbcwritelog($config['params']['trno'], $config, ' Action/Job Done ', 'CREATE - Description: ' . $row['rem']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow, 'reloadhead' => true];
            } else {
                $this->coreFunctions->sbcupdate($this->table, $data, ['joline' => $row['joline'], 'jotrno' => $row['jotrno'], 'empid' => $row['empid']]);
                $returnrow = $this->loaddataperrecord($config, $row['joline']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow, 'reloadhead' => true];
            }
        }
    } //end function
    public function lookupcallback($config)
    {
        switch ($config['params']['lookupclass2']) {
            case 'addtogrid':
                $data = [];
                $row = $config['params']['row'];
                $data['jotrno'] = $row['jotrno'];
                $data['joline'] = 0;
                $data['empid'] = $row['empid'];
                $data['rem'] = '';
                $data['clientname'] = $row['clientname'];
                $data['client'] = $row['client'];
                $data['bgcolor'] = 'bg-blue-2';
                return ['status' => true, 'msg' => 'Add Employee success...', 'data' => $data];
                break;
        }
    }
    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'additem':
                return $this->lookupemployee($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup'];
                break;
        }
    }
    public function lookupemployee($config)
    {
        $trno = $config['params']['tableid'];
        $lookupsetup = array(
            'type' => 'single',
            'title' => 'List of Passenger',
            'style' => 'width:900px;max-width:900px;'
        );

        $plotsetup = array(
            'plottype' => 'callback',
            'action' => 'addtogrid'
        );
        // lookup columns
        $cols = [];
        array_push($cols, array('name' => 'client', 'label' => 'Employee Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'));
        array_push($cols, array('name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'));

        $qry = "select $trno as jotrno, client ,clientname,clientid as empid from client where isemployee = 1 order by client,clientname";
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    } //end function
    private function selectqry()
    {
        $qry = 'hprrem.joline,hprrem.jotrno, hprrem.rem,client.client,client.clientname,client.clientid as empid';
        return $qry;
    }
    private function loaddataperrecord($config, $line)
    {
        $trno =  $config['params']['tableid'];
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . "  as hprrem
        left join client on client.clientid = hprrem.empid
        where hprrem.joline = ? and hprrem.jotrno = ? ";
        $data = $this->coreFunctions->opentable($qry, [$line, $trno]);
        return $data;
    }
    public function loaddata($config)
    {
        $trno = $config['params']['tableid'];
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . "  as hprrem 
        left join client on client.clientid = hprrem.empid
        where jotrno = ?
        order by joline desc";
        $data = $this->coreFunctions->opentable($qry, [$trno]);
        return $data;
    }
}
