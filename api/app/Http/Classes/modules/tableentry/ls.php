<?php

namespace App\Http\Classes\modules\tableentry;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\lookupClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class ls
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'LEAVE BATCH CREATION';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $lookupClass;
    private $table = 'leavebatch';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['code', 'codename', 'entitled', 'isnopay', 'count', 'numdays'];
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
        $this->lookupClass = new lookupClass;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 5408,
            'additem' => 5408
        );
        return $attrib;
    }


    public function createTab($config)
    {
        $columns = ['action', 'code', 'codename', 'entitled', 'isnopay', 'count', 'numdays'];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $columns]];
        $stockbuttons = ['delete', 'leaveentitledbtn'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$code]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$codename]['style'] = "width:400px;whiteSpace: normal;min-width:400px;";
        $obj[0][$this->gridname]['columns'][$entitled]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][$isnopay]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$count]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$numdays]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";

        $obj[0][$this->gridname]['columns'][$code]['type'] = 'lookup';
        $obj[0][$this->gridname]['columns'][$code]['action'] = 'lookupsetup';
        $obj[0][$this->gridname]['columns'][$code]['lookupclass'] = 'lookupcodename';
        $obj[0][$this->gridname]['columns'][$codename]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$count]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$numdays]['readonly'] = false;

        $obj[0][$this->gridname]['columns'][$numdays]['label'] = 'Days before effectivity';
        return $obj;
    }


    public function createtabbutton($config)
    {
        $tbuttons = ['addrecord', 'generateleave', 'saveallentry'];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }

    public function add($config)
    {
        $data = [];
        $data['line'] = 0;
        $data['code'] = '';
        $data['codename'] = '';
        $data['entitled'] = 0;
        $data['isnopay'] = 'false';
        $data['count'] = 0;
        $data['numdays'] = 0;
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    private function selectqry()
    {
        $qry = "leavebatch.line";
        foreach ($this->fields as $key => $value) {
            $qry = $qry . ',leavebatch.' . $value;
        }
        return $qry;
    }

    public function save($config)
    {
        $data = [];
        $row = $config['params']['row'];
        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }

        if ($row['line'] == 0) {
            $qry = "select code from " . $this->table . " where code='" . $data['code'] . "'";
            $checking = $this->coreFunctions->opentable($qry);
            if (!empty($checking)) return ['status' => false, 'msg' => 'Account leave already exists. - ' . $data['modulename']];
            $line = $this->coreFunctions->insertGetId($this->table, $data);
            if ($line != 0) {
                $returnrow = $this->loaddataperrecord($line);
                $this->logger->sbcmasterlog($line, $config, 'CREATE' . ' - ' . $data['codename'] . ' - ' . $data['entitled']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {
            $qry = "select code from " . $this->table . " where code='" . $data['code'] . "' and line<>'" . $row['line'] . "'";
            $checking = $this->coreFunctions->opentable($qry);
            if (!empty($checking)) return ['status' => false, 'msg' => 'Account leave already exists. - ' . $data['codename']];
            if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
                $returnrow = $this->loaddataperrecord($row['line']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        }
    } //end function

    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }
                if ($data[$key]['line'] == 0) {
                    if ($data[$key]['code'] == 'PT106') {
                        $data2['entitled'] = 365;
                    }
                    $checking = $this->coreFunctions->opentable("select code from " . $this->table . " where code='" . $data[$key]['code'] . "'");
                    if (!empty($checking)) return ['status' => false, 'msg' => 'Account Leave already exists. - ' . $data[$key]['codename']];
                    $line = $this->coreFunctions->insertGetId($this->table, $data2);
                } else {
                    $checking = $this->coreFunctions->opentable("select code from " . $this->table . " where code='" . $data[$key]['code'] . "' and line<>" . $data[$key]['line']);
                    if (!empty($checking)) return ['status' => false, 'msg' => 'Account leave already exists. - ' . $data[$key]['codename']];
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $returndata];
    } // end function

    public function delete($config)
    {
        $row = $config['params']['row'];

        $qry = "delete from " . $this->table . " where line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }

    private function loaddataperrecord($line)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor,(case when leavebatch.isnopay = 0 then 'false' else 'true' end) as isnopay ";
        $qry = "select " . $select . " 
                from " . $this->table . " where line=?";
        $data = $this->coreFunctions->opentable($qry, [$line]);
        return $data;
    }

    public function loaddata($config)
    {
        // $center = $config['params']['center'];
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor,acc.line as acnoid,(case when leavebatch.isnopay = 0 then 'false' else 'true' end) as isnopay ";
        $qry = "select " . $select . " 
                from " . $this->table . " 
                left join paccount as acc on acc.code=leavebatch.code 
                order by leavebatch.line";
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }

    public function tableentrystatus($config)
    {
        $config['params']['currentdate'] = $this->othersClass->getCurrentDate();
        $config['params']['year'] = date('Y', strtotime($config['params']['currentdate']));
        return $this->generateleave($config);
    }

    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];

        switch ($lookupclass2) {
            case 'lookuplogs':
                return $this->lookuplogs($config);
                break;
            case 'lookupcodename':
                return $this->lookupcodename($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
                break;
        }
    }

    public function generateleave($config, $blnLog = false)
    {
        $data = $config['params']['data'];
        $currentdate = $config['params']['currentdate'];
        $year = $config['params']['year'];
        $leavestart = $year . '-' . date('m-d', strtotime($this->companysetup->leavestart));
        $leaveend = $year . '-' . date('m-d', strtotime($this->companysetup->leaveend));

        $count = 0;
        $entitled = 0;
        $isnopay = 0;

        foreach ($data as $key => $value) {
            $leavebatch = $value['code'] . '-' . $year;
            $count = $value['count'];
            $entitled = $value['entitled'];
            $line = $value['line'];

            if ($value['isnopay'] == 'true') {
                $isnopay = 1;
            }

            $data = [
                'dateid' => date('Y-m-d'),
                'acnoid' => $value['acnoid'],
                'days' => 0,
                'bal' => 0,
                'prdstart' => $leavestart,
                'prdend' => $leaveend,
                'leavebatch' => $leavebatch,
                'isnopay' => $isnopay

            ];

            /////

            $hiredfield = 'hired';
            if ($value['numdays'] != 0) {
                $hiredfield = "DATE_SUB(hired, INTERVAL " . $value['numdays'] . " DAY)";
            }

            $qry = "select empid, hired, TIMESTAMPDIFF(YEAR, " . $hiredfield . ", '" . $currentdate . "') AS yrs from employee where isactive=1 and hired is not null";
            $employee_data = $this->coreFunctions->opentable($qry);

            if ($blnLog) $this->coreFunctions->sbclogger("Generating leave for " . $leavebatch . ". Total Employees " . count($employee_data), "DLOCK", true);

            if (!empty($employee_data)) {

                foreach ($employee_data as $key => $value) {
                    $data['empid'] = $value->empid;
                    $days = 0;
                    // $this->othersClass->logConsole('yrs: ' . $value->yrs);
                    if ($count == 0) {
                        if ($data['acnoid'] == 74) { //leave without pay
                            $days = $entitled;
                        } else {
                            if ($value->yrs >= 1) {
                                if ($entitled == 0) {
                                    goto checkleavehere;
                                } else {
                                    $days = $entitled;
                                }
                            }
                        }
                    } else {
                        checkleavehere:
                        $qry = "select first,last,days from leaveentitled where trno= '" . $line . "' and '" . $value->yrs . "' between first and last";
                        $chkrange = $this->coreFunctions->opentable($qry);
                        $result = json_decode(json_encode($chkrange), true);

                        if (!empty($chkrange)) {
                            $days = $result[0]['days'] + $entitled;
                        } else {
                            $days = $entitled;
                        }
                    }

                    $data['days'] = $days;
                    $data['bal'] = $days;

                    // $this->othersClass->logConsole('days: ' . $data['days']);
                    $exists = $this->coreFunctions->getfieldvalue("leavesetup", "trno", "empid=? and leavebatch=?", [$value->empid, $leavebatch]);

                    if ($exists) {
                        $applied = $this->coreFunctions->datareader("select ifnull(sum(adays),0) as value from leavetrans where trno=? and status='A'", [$exists]);
                        if ($applied == '') {
                            $applied = 0;
                        }

                        $data['bal'] = $data['days'] - $applied;
                        $data['prdstart'] = $leavestart;
                        $data['prdend'] = $leaveend;
                        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
                        $data['editby'] = 'AUTO';

                        unset($data['docno']);
                        $this->coreFunctions->sbcupdate('leavesetup', $data, ['trno' => $exists]);
                        // $this->voidprevious($data['empid'],  $data['dateid']);
                    } else {
                        if ($data['days'] > 0) {
                            $data['docno'] = $this->getlastDocno($config);
                            $this->coreFunctions->sbcinsert('leavesetup', $data);
                            // $this->voidprevious($data['empid'],  $data['dateid']);
                        }
                    }
                }
            }
        }
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'Successfully created.', 'data' => $returndata];
    }

    private function voidprevious($empid, $year)
    {
        $this->coreFunctions->execqry("update leavesetup set bal=0 where empid=" . $empid . " and bal<>0 and dateid<'" . $year . "'");
    }


    private function getlastDocno($config)
    {
        $docnolength = $this->companysetup->getdocumentlength($config['params']);
        $pref = app("App\Http\Classes\modules\payrollsetup\\leavesetup")->prefix;

        $length = strlen($pref);
        if ($length == 0) {
            $last = $this->coreFunctions->datareader('select docno as value from leavesetup order by trno desc limit 1');
        } else {
            $last = $this->coreFunctions->datareader('select docno as value from leavesetup where left(docno,?)=? order by trno desc limit 1', [$length, $pref]);
        }
        $start = $this->othersClass->SearchPosition($last);
        $seq = substr($last, $start) + 1;
        $poseq = $pref . $seq;
        $newdocno = $this->othersClass->PadJ($poseq, $docnolength);

        return $newdocno;
    }


    public function lookupcodename($config)
    {
        $lookupsetup = [
            'type' => 'single',
            'title' => 'Lists of Leave Accounts',
            'style' => 'width:500px;max-width:500px;height:400px'
        ];
        $plotsetup = [
            'plottype' => 'plotgrid',
            'plotting' => ['code' => 'code', 'codename' => 'codename']
        ];
        $index = $config['params']['index'];

        $cols = [
            ['name' => 'code', 'label' => 'Account Code', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'codename', 'label' => 'Account Name', 'align' => 'left', 'field' => 'codename', 'sortable' => true, 'style' => 'font-size:16px;']
        ];


        $data = $this->coreFunctions->opentable("
            select line as keyid,code,codename from paccount where codename like '%LEAVE%'
        ");
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
    }


    public function lookuplogs($config)
    {
        $lookupsetup = array(
            'type' => 'show',
            'title' => 'List of Logs',
            'style' => 'width:1000px;max-width:1000px;'
        );

        $trno = $config['params']['tableid'];
        $doc = $config['params']['doc'];

        $cols = [
            ['name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;']

        ];

        $qry = "select trno, doc, task, dateid, user
                from " . $this->tablelogs . "
                where doc = ?
                union all 
                select trno, doc, task, dateid, user
                from " . $this->tablelogs_del . "
                where doc = ?
                order by dateid desc";

        $data = $this->coreFunctions->opentable($qry, [$doc, $doc]);

        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
    }
} //end class
