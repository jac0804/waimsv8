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

class entrycounter
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'COUNTER';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'reqcategory';
    private $othersClass;
    public $style = 'width:100%;';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $fields = ['iscounter', 'code','color','isinactive'];
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
        $attrib = ['load' => 5579];
        return $attrib;
    }

    public function createTab($config)
    {
        $columns = ['action', 'code','color','isinactive', 'order'];
        $tab = [
            $this->gridname => [
                'gridcolumns' => $columns
            ]
        ];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $stockbuttons = ['save', 'delete', 'service'];

        $tab = [
            $this->gridname => [
                'gridcolumns' => $columns
            ]
        ];

        $obj = $this->tabClass->createTab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$order]['type'] = 'hidden';
        $obj[0][$this->gridname]['columns'][$order]['label'] = '';
        $obj[0][$this->gridname]['columns'][$code]['label'] = 'Counter';
        $obj[0][$this->gridname]['columns'][$code]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
        $obj[0][$this->gridname]['columns'][$order]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";


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
            " where iscounter = 1 " . $filtersearch . " order by line";

        Logger($qry);
        $data = $this->coreFunctions->opentable($qry);


        return $data;
    }

    private function selectqry()
    {
        $qry = "line,(case when isinactive=0 then 'false' else 'true' end) as isinactive";
        foreach ($this->fields as $key => $value) {
            if($value == 'isinactive'){
            }else{
                $qry = $qry . ',' . $value;
            }
            
        }
        return $qry;
    }

    public function add($config)
    {
        $data = [];
        $data['line'] = 0;
        $data['iscounter'] = 1;
        $data['code'] = '';
        $data['color'] = '';
        $data['bgcolor'] = 'bg-blue-2';
        $data['isinactive'] = 'false';
        return $data;
    }

    public function saveallentry($config)
    {
        $data = $config['params']['data'];

        foreach ($data as $key => $value) {
            if ($data[$key]['isinactive'] == 'true') {
                $data[$key]['isinactive'] = 1;
            } else {
                $data[$key]['isinactive'] = 0;
            }

            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }

                if (empty(trim($data2['code']))) {
                    return ['status' => false, 'msg' => 'Saving failed. Please input counter.'];
                }

                if ($data[$key]['line'] == 0) {
                    $line = $this->coreFunctions->insertGetId($this->table, $data2);
                    $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . $data[$key]['code']);
                } else {
                    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['editby'] = $config['params']['user'];
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
                    $this->logger->sbcmasterlog($data[$key]['line'], $config, ' UPDATE - ' . $data[$key]['code']);
                }
            }
        }
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
    }

    public function save($config)
    {
        $row = $config['params']['row'];
        $data = [];
        
        if ($row['isinactive'] == 'true') {
            $row['isinactive'] = 1;
        } else {
            $row['isinactive'] = 0;
        }
        
        foreach ($this->fields as $key2 => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }

        if (empty(trim($data['code']))) {
            return ['status' => false, 'msg' => 'Saving failed. Please input counter.'];
        }

        if ($row['line'] == 0) { // insert
            $line = $this->coreFunctions->insertGetId($this->table, $data);

            if ($line != 0) {
                $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . $data['code']);
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
                $this->logger->sbcmasterlog($row['line'], $config, ' UPDATE - ' . $data['code']);
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
        $qry = "select " . $select . " from " . $this->table . " where line=? and iscounter=1";
        $data = $this->coreFunctions->opentable($qry, [$line]);
        // var_dump($data);
        return $data;
    }

    public function delete($config)
    {
        $row = $config['params']['row'];

        $data = $this->loaddataperrecord($row['line']);
        $qry = "select counterline as value from (select counterline from currentservice where counterline=? union all select counterline from hcurrentservice where counterline=?) as a limit 1";
        $count = $this->coreFunctions->datareader($qry, [$row['line'],$row['line']],'',true);

        if ($count != 0) {
            return ['clientid' => $row['line'], 'status' => false, 'msg' => 'Already have transaction...'];
        }
        $qry = "delete from " . $this->table . " where line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
        $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE LINE: ' . $row['line'] . ' - ' . $row['code']);
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
    where log.doc = '" . $doc . "' and log.trno2 = 0
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and log.trno2 = 0";

        $qry = $qry . " order by dateid desc";
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
    }
}
