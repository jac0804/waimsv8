<?php

namespace App\Http\Classes\modules\accounting;

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

use Exception;
use PhpParser\Node\Expr\FuncCall;

class cutoffacctgbal
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Cut Off Accounting';
    public $gridname = 'entrygrid';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $sqlquery;
    private $logger;
    public $tablelogs = 'masterfile_log';
    public $style = 'width:100%;max-width:100%;';
    public $issearchshow = true;
    public $showclosebtn = false;
    public $acctgtable = 'acctgbal';
    public $reporter;


    public function __construct()
    {
        $this->btnClass = new buttonClass;
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->sqlquery = new sqlquery;
        $this->logger = new Logger;
        $this->reporter = new SBCPDF;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 4979,
            'view' => 4980,
            'save' => 4981,

        );
        return $attrib;
    }

    public function createHeadbutton($config)
    {
        return [];
    }
    public function createHeadField($config)
    {
        $fields = ['end', ['refresh', 'reset']];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'end.label', 'Cut off day');
        
        data_set($col1, 'refresh.label', 'Save');
        data_set($col1, 'refresh.action', 'save');
        data_set($col1, 'refresh.style', 'width:100px;whiteSpace: normal;min-width:100px;');
        data_set($col1, 'reset.style', 'width:100px;whiteSpace: normal;min-width:100px;');

        return array('col1' => $col1);
    }
    public function data($config)
    {
        return $this->paramsdata($config);
    }
    public function createTab($config)
    {
        $tab = [];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        return $obj;
    }
    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }
    public function paramsdata($config)
    {
        $cutoff = "curdate()";

        $cutoffexists = $this->coreFunctions->getfieldvalue('profile', 'pvalue', "doc='CTA' and psection='ACCTGCUTOFF'");
        if ($cutoffexists != '') {
            $cutoff = "'" . $cutoffexists . "'";
        }

        if (isset($config['params']['dataparams'])) {
            $cutoff = $config['params']['dataparams'];
        }
        $qry = "select " . $cutoff . " as `end`";

        $data = $this->coreFunctions->opentable($qry);
        return $data[0];
    }
    public function headtablestatus($config)
    {
        $action = $config['params']["action2"];
        switch ($action) {
            case 'save':
                return $this->loaddata($config);
                break;
            case 'reset':
                return $this->resetdata($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Please check headtablestatus (' . $action . ')'];
                break;
        }
    }
    private function loaddata($config)
    {
        ini_set('max_execution_time', -1);
        ini_set('memory_limit', '-1');
        $cutoffdate = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
        $query = "
select ifnull(clientid,0) as clientid,acnoid,projectid,db,cr, '$cutoffdate' as dateid,deptid,center,branch as branchid from  (
select client.clientid as clientid,acnoid, sum(db) as db,sum(cr) as cr,head.deptid,num.center,detail.projectid,detail.branch from ladetail as detail
left join lahead as head on head.trno = detail.trno
left join cntnum as num on num.trno = head.trno
left join client on client.client = detail.client
where date(dateid) <= '$cutoffdate'
group by client.clientid,acnoid,detail.projectid,head.deptid,detail.branch,num.center
union all
select detail.clientid, acnoid,sum(db) as db , sum(cr) as cr,head.deptid,num.center,detail.projectid,detail.branch from gldetail as detail
left join glhead as head on head.trno = detail.trno
left join cntnum as num on num.trno = head.trno
where date(head.dateid) <= '$cutoffdate'
group by detail.clientid,detail.acnoid,detail.projectid,head.deptid,detail.branch,num.center
) as acctb";


        $data = $this->coreFunctions->opentable($query);


        if (!empty($data)) {
            $delete = $this->coreFunctions->execqry('delete from acctgbal ', 'delete');
            if ($delete == 1) {

                $datachuck = array_chunk($data, 100);

                foreach ($datachuck as $key => $chuck) {

                    $chuckdata = array_map(function ($value) {
                        return [
                            'acnoid' => $value->acnoid,
                            'clientid'   => $value->clientid,
                            'projectid'    => $value->projectid,
                            'center' => $value->center,
                            'deptid' => $value->deptid,
                            'db'    => $value->db,
                            'cr' => $value->cr,
                            'dateid' => $value->dateid,
                            'branchid' => $value->branchid
                        ];
                    }, $chuck);
                    // 100 row isnsert data per loop
                    $result = $this->coreFunctions->sbcinsert($this->acctgtable, $chuckdata);
                    if (!$result) {
                        return ['status' => false, 'msg' => ' insert failed ', 'action' => 'load'];
                    }
                }

                if ($result == 1) {
                    $this->logger->sbcmasterlog(0, $config, "AUTO GENERATE - ACCTGCUTOFF");
                    $this->adprofile($cutoffdate);
                    return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
                }
            } else {
                return ['status' => false, 'msg' => 'The acctgbal table does not exist. Check fields first.', 'action' => 'load'];
            }
        } else {
            return ['status' => false, 'msg' => 'There was no data found within the cutoff date.', 'action' => 'load'];
        }
    }
    public function resetdata($config)
    {
        $getcutoffline = $this->coreFunctions->getfieldvalue("profile", "line", "doc='CTA' and psection='ACCTGCUTOFF'");
        $datacount = $this->coreFunctions->datareader('select count(acnoid) as value from acctgbal');
        $msg = '';
        if (!empty($datacount)) {
            $this->coreFunctions->execqry('delete from acctgbal ', 'delete');
            $this->coreFunctions->execqry('delete from profile where line =? and psection =? ', 'delete', [$getcutoffline, 'ACCTGCUTOFF']);
        } else {
            $msg = 'No data was found to reset.';
        }
        if (empty($msg)) {
            $this->logger->sbcmasterlog(0, $config, "RESET - ACCTGCUTOFF");
            $msg = 'Inventory cut off was successfully reset.';
        }
        return ['status' => true, 'msg' => $msg, 'action' => 'load'];
    }
    public function adprofile($cutoffdate)
    {
        $getcutoffline = $this->coreFunctions->getfieldvalue("profile", "line", "doc='CTA' and psection='ACCTGCUTOFF'");
        if ($getcutoffline == 0) {
            $data = ['doc' => 'CTA', 'psection' => 'ACCTGCUTOFF', 'pvalue' => $cutoffdate];
            $this->coreFunctions->sbcinsert("profile", $data);
        } else {
            $this->coreFunctions->sbcupdate("profile", ['pvalue' => $cutoffdate], ['line' => $getcutoffline, 'psection' => 'ACCTGCUTOFF']);
        }
    }
}
