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

class viewbranchjob
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'JOB TITLE';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $logger;
    private $table = 'cljobs';
    private $othersClass;
    public $style = 'width:500px;max-width:500px;';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $fields = ['qty', 'clientid', 'jobid'];
    public $showclosebtn = false;
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
        $columns = ['action', 'jobtitle', 'qty', 'itemname'];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $columns]];

        $stockbuttons = ['save', 'delete'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:50px;whiteSpace: normal;min-width:50px;";
        $obj[0][$this->gridname]['columns'][$itemname]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][$qty]['label'] = "Allocation";
        $obj[0][$this->gridname]['columns'][$qty]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";

        $obj[0][$this->gridname]['columns'][$jobtitle]['type'] = "lookup";
        $obj[0][$this->gridname]['columns'][$jobtitle]['class'] = "csjobtitle sbccsreadonly";
        $obj[0][$this->gridname]['columns'][$jobtitle]['action'] = "lookupsetup";
        $obj[0][$this->gridname]['columns'][$jobtitle]['lookupclass'] = "lookupbranchjob";
        // $obj[0][$this->gridname]['columns'][$itemname]['type'] = "hidden";


        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }


    public function createtabbutton($config)
    {
        $tbuttons = ['addrecord', 'saveallentry', 'masterfilelogs'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }


    public function add($config)
    {
        $data = [];
        $data['line'] = 0;
        $data['jobid'] = 0;
        $data['jobtitle'] = '';
        $data['clientid'] = $config['params']['tableid'];
        $data['qty'] = 0;
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        $tableid = $config['params']['tableid'];
        $companyid = $config['params']['companyid'];
        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2], '', $companyid);
                }
                $data2['clientid'] = $tableid;
                if ($data[$key]['line'] == 0) {
                    $line = $this->coreFunctions->insertGetId($this->table, $data2);
                    $config['params']['doc'] = strtoupper("BRANCH_JOB_LIST");
                    $this->logger->sbcmasterlog($tableid, $config, ' CREATE - ' . ', LINE: ' . $line . ' Quantity: ' . $data2['qty'] . ' Job title: ' . $data[$key]['jobtitle']);
                } else {
                    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['editby'] = $config['params']['user'];
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['clientid' => $data2['clientid'], 'line' => $data[$key]['line']]);
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
        $tableid = $config['params']['tableid'];
        $companyid = $config['params']['companyid'];
        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }

        // $data['clientid'] = $tableid;
        if ($row['line'] == 0) {
            $line = $this->coreFunctions->insertGetId($this->table, $data);

            if ($line != 0) {
                $returnrow = $this->loaddataperrecord($config, $line);

                $config['params']['doc'] = strtoupper("BRANCH_JOB_LIST");
                $this->logger->sbcmasterlog($tableid, $config, ' CREATE - ' . ', LINE: ' . $line . ' Quantity: ' . $row['qty'] . ' Job title: ' . $row['jobtitle']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            if ($this->coreFunctions->sbcupdate($this->table, $data, ['clientid' => $row['clientid'], 'line' => $row['line']]) == 1) {
                $returnrow = $this->loaddataperrecord($config, $row['line']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        }
    } //end function

    public function delete($config)
    {
        $tableid = $config['params']['tableid'];
        $row = $config['params']['row'];
        $qry = "delete from " . $this->table . " where  clientid=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['clientid'], $row['line']]);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }

    private function selectqry()
    {
        $select = " client.clientname,clj.qty,clj.line,clj.clientid,jobt.jobtitle,clj.jobid ";

        return $select;
    }

    private function loaddataperrecord($config, $line)
    {
        $tableid = $config['params']['tableid'];
        $select = $this->selectqry();

        $qry = "select $select ,'' as bgcolor
              from cljobs as clj 
              left join client  on client.clientid=clj.clientid
              left join jobthead as jobt on jobt.line=clj.jobid
              where clj.clientid = ? and clj.line = ?";


        $data = $this->coreFunctions->opentable($qry, [$tableid, $line]);
        return $data;
    }

    public function loaddata($config)
    {
        $tableid = $config['params']['tableid'];
        $select = $this->selectqry();

        $qry = "select $select ,'' as bgcolor
                from cljobs as clj 
                left join client on client.clientid=clj.clientid
                left join jobthead as jobt on jobt.line=clj.jobid
                where clj.clientid = ? ";
        $data = $this->coreFunctions->opentable($qry, [$tableid]);
        return $data;
    }


    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {

            case 'lookuplogs':
                return $this->lookuplogs($config);
                break;
            case 'lookupbranchjob':
                return $this->lookupjobtitle($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup'];
                break;
        }
    }

    public function s($config)
    {
        $rowindex = $config['params']['index'];
        $lookupclass2 = $config['params']['lookupclass2'];

        $lookupsetup = array(
            'type' => 'single',
            'title' => 'List of Department',
            'style' => 'width:900px;max-width:900px;'
        );

        $plotsetup = array(
            'plottype' => 'plotgrid',
            'plotting' => ['deptid' => 'clientid', 'dept' => 'clientname']
        );

        $cols = array(
            array('name' => 'client', 'label' => 'Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'),
        );

        $qry = "select client, clientname, clientid from client
      where isdepartment = 1";

        $data = $this->coreFunctions->opentable($qry);

        return [
            'status' => true,
            'msg' => 'ok',
            'data' => $data,
            'lookupsetup' => $lookupsetup,
            'cols' => $cols,
            'plotsetup' => $plotsetup,
            'index' => $rowindex
        ];
    }

    public function lookupjobtitle($config)
    {
        $rowindex = $config['params']['index'];
        $plotting = array(
            'jobid' => 'line',
            'jobcode' => 'docno',
            'jobtitle' => 'jobtitle',
            'jobdesc' => 'jobdesc',
            'emptitle' => 'docno',
            'job' => 'docno',
        );
        $plottype = 'plotgrid';

        $title = 'List of Jobs';

        $lookupsetup = array(
            'type' => 'single',
            'title' => $title,
            'style' => 'width:900px;max-width:900px;'
        );


        $plotsetup = array(
            'plottype' => $plottype,
            'plotting' => $plotting
        );
        // lookup columns
        $cols = [
            ['name' => 'docno', 'label' => 'Code', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'jobtitle', 'label' => 'Job Title', 'align' => 'left', 'field' => 'jobtitle', 'sortable' => true, 'style' => 'font-size:16px;']
        ];


        $qry = "select j.line,j.docno,j.jobtitle,ifnull(group_concat(jt.description),'') as jobdesc 
                from jobthead as j 
                left join jobtdesc as jt on jt.trno = j.line  
                group by j.line,j.docno,j.jobtitle order by j.docno";

        $data = $this->coreFunctions->opentable($qry);

        return [
            'status' => true,
            'msg' => 'ok',
            'data' => $data,
            'lookupsetup' => $lookupsetup,
            'cols' => $cols,
            'plotsetup' => $plotsetup,
            'index' => $rowindex
        ];
    } //end function


    public function lookuplogs($config)
    {
        $doc = strtoupper("BRANCH_JOB_LIST");

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
    where log.doc = '" . $doc . "' and (log.trno = '" . $trno . "' or log.trno2 = '" . $trno . "')
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and (log.trno = '" . $trno . "' or log.trno2 = '" . $trno . "')";

        $qry = $qry . " order by dateid desc";
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
    }


    // -> Print Function
    public function reportsetup($config)
    {
        return [];
    }


    public function createreportfilter()
    {
        return [];
    }

    public function reportparamsdata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        return [];
    }
} //end class
