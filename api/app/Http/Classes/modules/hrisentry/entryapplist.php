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
use App\Http\Classes\lookup\hrislookup;

class entryapplist
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'APPLICANT LISTS';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'hpersonreqdetail';
    public $tablelogs = 'masterfile_log';
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['trno', 'line', 'appid'];
    public $showclosebtn = true;
    private $logger;
    private $hrislookup;


    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->hrislookup = new hrislookup;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createTab($config)
    {
        $getcols = ['empname', 'status', 'statname'];

        foreach ($getcols as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $getcols]];

        $stockbuttons = []; //acnoid
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        // $obj[0][$this->gridname]['columns'][$action]['style'] = "width:40px;whiteSpace: normal;min-width:40px;"; //action
        $obj[0][$this->gridname]['columns'][$empname]['style'] = "width:200px;whiteSpace: normal;min-width:200px;"; //action
        $obj[0][$this->gridname]['columns'][$status]['label'] = 'Status';
        $obj[0][$this->gridname]['columns'][$statname]['label'] = 'Applicant Portfolio';

        $obj[0][$this->gridname]['columns'][$empname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$status]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$statname]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$status]['align'] = 'left';

        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = []; //'addapplist', 'deleteallitem'
        $obj = $this->tabClass->createtabbutton($tbuttons);
        // $obj[1]['label'] = 'Delete all';
        // $obj[1]['lookupclass'] = 'loaddata';
        return $obj;
    }

    public function lookupsetup($config)
    {
        $lookupclass = $config['params']['lookupclass2'];
        switch ($lookupclass) {
            case 'addapplist':
                return $this->hrislookup->lookupapplists($config);
                break;
        }
    }

    public function lookupcallback($config)
    {
        $id = $config['params']['tableid'];
        $row = $config['params']['rows'];
        $data = [];
        foreach ($row  as $key2 => $value) {
            $config['params']['data']['line'] = 0;
            $config['params']['data']['trno'] = $id;
            $config['params']['data']['appid'] = $value['appid'];
            $config['params']['data']['bgcolor'] = 'bg-blue-2';
            $return = $this->save('insert', $config);
            if ($return['status']) {
                array_push($data, $return['row'][0]);
            }
        }

        return ['status' => true, 'msg' => 'Successfully added.', 'data' => $data];
    } // end function

    public function save($action, $config)
    {
        $data = [];
        $row = $config['params']['data'];
        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }
        $data['trno'] = $config['params']['tableid'];
        if ($row['line'] == 0) {
            $qry = "select line as value from " . $this->table . " where trno=? order by line desc limit 1";
            $line = $this->coreFunctions->datareader($qry, [$data['trno']]);
            if (!$line) {
                $line = 0;
            }
            $line = $line + 1;
            $data["line"] = $line;
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            if ($this->coreFunctions->sbcinsert($this->table,  $data)) {
                $returnrow = $this->loaddataperrecord($data['trno'], $line, $config);
                $this->logger->sbcmasterlog(
                    $data['trno'],
                    $config,
                    'CREATE' . ' - APPLICANT: ' . $returnrow[0]->empname . ' - LINE' . $line
                );
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        }
    } //end function

    public function delete($config)
    {
        $row = $config['params']['row'];
        $qry = "delete from hpersonreqdetail where trno=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line']]);

        $this->logger->sbcmasterlog(
            $row['trno'],
            $config,
            'CREATE' . ' - APPLICANT: ' . $row['empname']
        );

        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }

    public function deleteallitem($config)
    {
        $trno = $config['params']['tableid'];
        $qry = "delete from hpersonreqdetail where trno=?";
        $this->coreFunctions->execqry($qry, 'delete', [$trno]);
        return ['status' => true, 'msg' => 'Successfully deleted.', 'data' => []];
    }

    private function loaddataperrecord($clientid, $line)
    {
        $select = $this->selectqry();
        $qry = $select . " where detail.trno=? and detail.line=?";
        $data = $this->coreFunctions->opentable($qry, [$clientid, $line]);
        return $data;
    }

    private  function selectqry()
    {
        // return "select detail.trno,detail.line,detail.appid, concat(app.empfirst,' ',app.empmiddle,' ',app.emplast) as empname, app.jstatus as statname, tr.status as status
        //         from hpersonreqdetail as detail  
        //         left join app on app.empid=detail.appid left join trxstatus tr on tr.line=app.statid";

        return  "select concat(app.empfirst,' ',app.empmiddle,' ',app.emplast) as empname, app.jstatus as statname, tr.status as status from app left join trxstatus tr on tr.line=app.statid ";
    }

    public function loaddata($config)
    {
        $tableid = $config['params']['tableid'];
        $select = $this->selectqry();
        // $qry = $select . " where detail.trno=?";
        $qry = $select . " where app.hqtrno=?";
        $data = $this->coreFunctions->opentable($qry, [$tableid]);
        return $data;
    }
} //end class
