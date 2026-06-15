<?php

namespace App\Http\Classes\modules\autoserventry;

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

class entryjobs
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Jobs';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'ptjobs';
    private $othersClass;
    public $style = 'width:100%;';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $fields = ['trno', 'jobid', 'rem']; //, 'packageline'
    public $showclosebtn = false;
    private $enrollmentlookup;
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
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createTab($config)
    {
        // var_dump($config['params']);
        // break;
        $columns = ['action',  'code', 'description', 'rem']; // 'packname'
        $tab = [$this->gridname => ['gridcolumns' => $columns]];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $stockbuttons = ['save', 'addtask', 'delete'];
        $tab = [$this->gridname => ['gridcolumns' => $columns]];
        $obj = $this->tabClass->createTab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$code]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$description]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$rem]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
        // $obj[0][$this->gridname]['columns'][$packname]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
        // $obj[0][$this->gridname]['columns'][$packname]['label'] = 'Package';

        $obj[0][$this->gridname]['columns'][$code]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$description]['readonly'] = true;

        $obj[0][$this->gridname]['columns'][$action]['btns']['addtask']['name'] = 'multigrid';
        $obj[0][$this->gridname]['columns'][$action]['btns']['addtask']['action'] = 'autoserventry';
        $obj[0][$this->gridname]['columns'][$action]['btns']['addtask']['lookupclass'] = 'entrytlabor';
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['addoutlet', 'saveallentry', 'whlog']; //'whlog'
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[0]['lookupclass'] = 'addjob';
        $obj[0]['action'] = 'lookupsetup';
        return $obj;
    }

    public function loaddata($config)
    {
        $trno = $config['params']['tableid'];
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
        $qry = "select " . $select . " from " . $this->table . " as pt
        left join jobthead as jt on jt.line = pt.jobid 
        where pt.trno = ?";
        $data = $this->coreFunctions->opentable($qry, [$trno]);
        return $data;
    }

    private function selectqry()
    {
        $query = "pt.line,pt.jobid,pt.trno as trno,pt.line as jobline,pt.rem,jt.docno as code,jt.jobtitle as description";
        return $query;
    }

    public function add($config)
    {
        $data = [];
        return $data;
    }

    public function saveallentry($config)
    {
        $doc = $config['params']['doc'];
        $data = $config['params']['data'];
        $trno = $config['params']['tableid'];
        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }
                if ($data[$key]['line'] == 0) {
                    $data['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
                    $data['encodedby'] = $config['params']['user'];

                    $config['params']['doc'] = 'ENTRYJOB';
                    $line = $this->coreFunctions->insertGetId($this->table, $data2);
                    $jobs = $this->coreFunctions->opentable("select docno,jobtitle from jobthead where line =?", [$data2['jobid']]);
                    $this->logger->sbcmasterlog($trno, $config, ' CREATE - Line: ' . $line . ' ' . 'Job Code : ' . $jobs[0]->docno . ' ' . 'Job Desc : ' . $jobs[0]->jobtitle);
                } else {
                    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['editby'] = $config['params']['user'];
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['trno' => $config['params']['tableid'], 'line' => $data[$key]['line']]);
                }
            }
        }
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
    }

    public function save($config)
    {
        $doc = $config['params']['doc'];
        $row = $config['params']['row'];
        $trno = $config['params']['tableid'];
        $data = [];
        foreach ($this->fields as $key2 => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }

        if ($row['line'] == 0) { // insert
            $data['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
            $data['encodedby'] = $config['params']['user'];

            $line = $this->coreFunctions->datareader("select line as value from " . $this->table . " where trno =? order by line desc limit 1", [$trno], '', true);
            if ($line == 0) {
                $line = 1;
            } else {
                $line = $line + 1;
            }
            $data['line'] = $line;
            if ($this->coreFunctions->sbcinsert($this->table, $data)) {
                $config['params']['doc'] = 'ENTRYJOB';
                $jobs = $this->coreFunctions->opentable("select docno,jobtitle from jobthead where line =?", [$data['jobid']]);
                $this->logger->sbcmasterlog($trno, $config, ' CREATE - Line: ' . $line . ' ' . 'Job Code : ' . $jobs[0]->docno . ' ' . 'Job Desc : ' . $jobs[0]->jobtitle);
                $returnrow = $this->loaddataperrecord($config, $line);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else { // update
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            $update = $this->coreFunctions->sbcupdate($this->table, $data, ['trno' => $config['params']['tableid'], 'line' => $row['line']]);
            if ($update) {
                $returnrow = $this->loaddataperrecord($config, $row['line']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Update failed.'];
            }
        }
    } // end function

    private function loaddataperrecord($config, $line)
    {
        $trno = $config['params']['tableid'];
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " as pt 
        left join jobthead as jt on jt.line = pt.jobid 
        where pt.trno = $trno and  pt.line =?";
        $data = $this->coreFunctions->opentable($qry, [$line]);
        // var_dump($data);
        return $data;
    }

    public function delete($config)
    {
        $row = $config['params']['row'];
        $doc = $config['params']['doc'];
        $trno = $config['params']['tableid'];
        $qry = "delete from " . $this->table . " where line=? and trno=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line'], $config['params']['tableid']]);
        $config['params']['doc'] = 'ENTRYJOB';
        $jobs = $this->coreFunctions->opentable("select docno,jobtitle from jobthead where line =?", [$row['jobid']]);
        $this->logger->sbcdelmaster_log($trno, $config, 'REMOVE LINE: ' . $row['line'] . ' - Job Code: ' . $jobs[0]->docno . ' ' . 'Job Desc : ' . $jobs[0]->jobtitle);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }

    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'whlog':
                return $this->lookuplogs($config);
                break;
            case 'addjob':
                return $this->addjob($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
                break;
        }
    }


    public function addjob($config)
    {
        $lookupsetup = array(
            'type' => 'multi',
            'rowkey' => 'jobid',
            'title' => 'List of Jobs',
            'style' => 'width:800px;max-width:800px;'
        );

        $plotsetup = array(
            'plottype' => 'tableentry',
            'action' => 'addtogrid'
        );

        // lookup columns
        $cols = [
            ['name' => 'code', 'label' => 'Code', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'description', 'label' => 'Job description', 'align' => 'left', 'field' => 'description', 'sortable' => true, 'style' => 'font-size:16px;']
        ];
        $qry = "select line as jobid,docno as code, jobtitle as description from jobthead  order by jobtitle";
        $data = $this->coreFunctions->opentable($qry);

        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }

    public function lookupcallback($config)
    {
        $doc = $config['params']['doc'];
        $trno = $config['params']['tableid'];
        $row = $config['params']['rows'];
        $returndata = [];
        $status = true;
        $msg = 'Successfully added.';
        foreach ($row  as $key2 => $value) {
            $config['params']['row']['line'] = 0;
            $config['params']['row']['trno'] = $trno;
            $config['params']['row']['jobid'] = $row[$key2]['jobid'];
            $config['params']['row']['rem'] = '';
            $config['params']['row']['bgcolor'] = '';
            $return = $this->save($config);
            if ($return['status']) {
                array_push($returndata, $return['row'][0]);
            } else {
                $status = false;
                $msg = $return['msg'];
            }
        }

        return ['status' => $status, 'msg' => $msg, 'data' => $returndata];
    } // end function

    public function lookuplogs($config)
    {
        $doc = 'ENTRYJOB';
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
        select log.trno, log.doc, log.task, log.user, log.dateid, 
        if(u.pic='','blank_user.png',u.pic) as pic
        from " . $this->tablelogs . " as log
        left join useraccess as u on u.username=log.user
        left join ptjobs as pt on pt.line = log.trno
        where log.doc = '" . $doc . "'
        union all
        select log.trno, log.doc, log.task, log.user, log.dateid, 
        if(u.pic='','blank_user.png',u.pic) as pic
        from  " . $this->tablelogs_del . " as log
        left join useraccess as u on u.username=log.user
        left join ptjobs as pt on pt.line = log.trno 
        where log.doc = '" . $doc . "' ";
        $qry = $qry . " order by dateid desc";
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
    }
} //end class
