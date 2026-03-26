<?php

namespace App\Http\Classes\modules\ahris;

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
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;
use Illuminate\Support\Facades\Storage;

class contractmonitoring
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    private $companysetup;
    private $modulename = 'CONTRACT MONITORING';
    private $coreFunctions;
    private $othersClass;
    private $config = [];
    public $gridname = 'editgrid';

    public function __construct()
    {
        $this->btnClass = new buttonClass;
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 5210,
            'deleteitem' => 5467,
            'viewevaluators' => 5466,
            'addevaluators' => 5467
        );
        return $attrib;
    }

    public function loadaform($config)
    {
        ini_set('max_execution_time', -1);

        $stage = $this->coreFunctions->opentable("select line, description, sortline, 1 as type, 'blue' as bgcolor 
        from regularization where isdays=1 and isinactive=0 
        union all 
        select 'XXX' as line, 'EXPIRED CONTRACT' as description, 0 as sortline, 2 as type, 'red' as bgcolor 
        order by type, sortline");

        if (!empty($stage)) {
            foreach ($stage as $key => $value) {
                $this->regularizationcount($value->line, $value->bgcolor, $value->description, $config);
            }
        };

        $sorting = ['qcard', 'actionlist', 'dailynotif', 'sbcgraph', 'sbclist'];
        return [
            'status' => true,
            'msg' => 'Loaded Success',
            'obj' => $this->config,
            'sorting' => $sorting
        ];
    }

    public function getstyle($config)
    {
        switch ($config['params']['lookupclass2']) {
            case 'loadregprocess':
                return 'width:100%;max-width:60%;';
                break;
        }
    }

    public function getmodulename($config)
    {
        switch ($config['params']['lookupclass2']) {
            case 'loadregprocess':
                return 'CONTRACT MONITORING';
                break;
        }
    }

    public function getgridname($config)
    {
        switch ($config['params']['lookupclass2']) {
            case 'loadregprocess':
                return 'editgrid';
                break;
        }
    }

    public function getisshowsearch($config)
    {
        return false;
    }

    public function getshowclosebtn($config)
    {
        return true;
    }

    public function createHeadField($config)
    {
        // var_dump($config['params']);
        switch ($config['params']['lookupclass2']) {
            case 'loadregprocess':
                return $this->regHeadField($config);
                break;
        }
    }

    public function paramsdata($config)
    {
        $empid = $config['params']['classid']['empid'];
        $regid = $config['params']['classid']['regid'];
        $stat = $config['params']['classid']['stat'];

        switch ($config['params']['lookupclass2']) {
            case 'loadregprocess':
                // return $this->coreFunctions->opentable("select concat(client.clientname,'-waw') as empname,date(reg.expiration) as expiration, 
                //                     reg.regid, reg.empid, date(emp.hired) as hired,date(reg.reaccess) as reaccess, reg.line
                //  from regprocess as reg
                //  left join client on client.clientid=reg.empid
                //  left join employee as emp on emp.empid=client.clientid
                //  where reg.empid=$empid and reg.regid=$regid");
                return $this->coreFunctions->opentable("select client.clientname as empname, date(emp.hired) as hired,reg.regid,reg.empid,date(reg.reaccess) as reaccess,
                    date(reg.evaluated) as evaluated,'" . $stat . "' as stat, reg.line, date(reg.expiration) as expiration
                    from regprocess as reg
                    left join client on client.clientid=reg.empid
                    left join employee as emp on emp.empid=client.clientid
                    where reg.empid=$empid and reg.regid=$regid");
                break;
        }
    }

    public function data($config)
    {
        switch ($config['params']['lookupclass2']) {
            case 'loadregprocess':
                return $this->loadrow($config);
                break;
        }
    }

    public function createTab($config)
    {
        switch ($config['params']['lookupclass2']) {
            case 'loadregprocess':
                $accessview = $this->othersClass->checkAccess($config['params']['user'], 5466);
                if ($accessview == 0) return [];

                $this->gridname = 'editgrid';
                $tab = [$this->gridname => ['gridcolumns' => ['action', 'name', 'dateid']]];
                $stockbuttons = ['delete'];
                $access = $this->othersClass->checkAccess($config['params']['user'], 5467);
                if ($access == 0) $stockbuttons = [];
                $obj = $this->tabClass->createtab($tab, $stockbuttons);
                $obj[0][$this->gridname]['columns'][1]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][1]['label'] = 'Evaluator';
                $obj[0][$this->gridname]['columns'][1]['align'] = 'left';
                $obj[0][$this->gridname]['columns'][0]['style'] = 'width:150px;max-width:150px;min-width:150px;white-space:normal;';
                $obj[0][$this->gridname]['columns'][1]['style'] = 'width:500px;max-width:500px;';
                $obj[0][$this->gridname]['columns'][2]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][2]['label'] = 'Date Evaluated';
                $obj[0][$this->gridname]['columns'][2]['style'] = 'width:150px;max-width:150px;';
                $obj[0][$this->gridname]['descriptionrow'] = null;
                return $obj;
                break;
        }
    }

    public function createtabbutton($config)
    {
        switch ($config['params']['lookupclass2']) {
            case 'loadregprocess':
                $tbuttons = [];
                $obj = $this->tabClass->createtabbutton($tbuttons);

                return $obj;
                break;
        }
    }

    private function regularizationcount($doc, $color, $caption, $config)
    {
        $currentdate = $this->othersClass->getCurrentDate();

        $logintype = $config['params']['logintype'];
        if ($logintype == '62608e08adc29a8d6dbc9754e659f125') {
            $adminid = $config['params']['adminid'];

            $viewall = $this->othersClass->checkAccess($config['params']['user'], 5468);
            if ($viewall == 1) goto viewAllHere;

            $pendingapp = $this->coreFunctions->opentable("select line from pendingapp where clientid=" . $adminid . " and doc='CONTRACTMONITORING'");
            $employee = $employees = [];
            if (!empty($pendingapp)) {
                foreach ($pendingapp as $app) {
                    $employees = $this->coreFunctions->opentable("select 'P' as stat, client.clientname, date(p.expiration) as expiration,
                        p.regid, p.empid, date(emp.hired) as hired, p.line
                        from regprocess as p
                        left join client on client.clientid=p.empid
                        left join employee as emp on emp.empid=client.clientid
                        where p.line=" . $app->line . " and p.regid='" . $doc . "' and p.evaluated is null and (DATEDIFF(now(), emp.hired) <= 180
                        or (select date(reaccess) from regprocess as reg where reg.empid=emp.empid order by reaccess desc limit 1) is null)");
                    if (!empty($employees)) {
                        foreach ($employees as $emps) {
                            array_push($employee, $emps);
                        }
                    }
                }
            }
        } else {
            viewAllHere:
            $employee = $this->coreFunctions->opentable("select 'P' as stat,client.clientname, date(p.expiration) as expiration, 
                                        p.regid, p.empid, date(emp.hired) as hired, p.line
                            from regprocess as p 
                            left join client on client.clientid=p.empid 
                            left join employee as emp on emp.empid=client.clientid
                            where p.regid='" . $doc . "' and p.evaluated is null and (DATEDIFF(now(), emp.hired) <= 180
                                    or (select date(reaccess) from regprocess as reg where reg.empid=p.empid order by reaccess desc limit 1) is not null)");

            if ($doc == 'XXX') {
                $employee = $this->coreFunctions->opentable("select 'E' as stat,client.clientname, date(DATE_ADD(emp.hired, INTERVAL 180 DAY)) as expiration, 
                                        p.regid, p.empid, date(emp.hired) as hired, p.line
                            from regprocess as p 
                            left join client on client.clientid=p.empid 
                            left join employee as emp on emp.empid=client.clientid
                            where  p.evaluated is null  and (DATEDIFF(now(), emp.hired) > 180 and (select date(reaccess) from regprocess as reg where reg.empid=p.empid order by reaccess desc limit 1) is null)");
            }
        }


        $detail = [];
        foreach ($employee as $key => $value) {
            $classid = [
                'regid' => $value->regid,
                'empid' => $value->empid,
                'stat' => $value->stat,
                'regline' => $value->line
            ];

            $detail["btn" . $doc . $value->empid] = [
                'label' => $value->clientname . ' (' . $value->expiration . ')',
                'type' => 'customform',
                'action' => 'loadregprocess',
                'lookupclass' => 'loadregprocess',
                'classid' => $classid
            ];
        }

        $this->config['qcard'][$doc] =
            [
                'class' => 'bg-' . $color . ' text-white',
                'headalign' => 'left',
                'title' => $caption,
                'subtitle' => count($employee),
                'titlesize' => '20px',
                'subtitlesize' => '25px',
                'object' => 'btn',
                'isvertical' => true,
                'align' => 'left',
                'detail' => $detail
            ];
    }

    public function regHeadField($config)
    {
        $logintype = $config['params']['logintype'];
        $stat = $config['params']['classid']['stat'];
        $access = $this->othersClass->checkAccess($config['params']['user'], 5467);
        $this->coreFunctions->logConsole('5467-' . $access);
        $fields = [];
        if ($access) {
            $fields = ['empname', 'hired', 'expiration', 'refresh', 'reset'];
            $col1 = $this->fieldClass->create($fields);
            if ($stat == 'P') {
                data_set($col1, 'refresh.label', 'EVALUATED');
            } else {
                data_set($col1, 'refresh.label', 'REACCESS');
            }
            data_set($col1, 'reset.label', 'Add evaluator');
            data_set($col1, 'reset.confirm', false);
            data_set($col1, 'reset.action', 'lookupemployee');
            data_set($col1, 'reset.lookupclass', 'lookupsetup');
        } else {
            $fields = ['empname', 'hired', 'expiration', 'refresh'];
            $col1 = $this->fieldClass->create($fields);
            if ($stat == 'P') {
                data_set($col1, 'refresh.label', 'EVALUATED');
            } else {
                data_set($col1, 'refresh.label', 'REACCESS');
            }
        }
        return array('col1' => $col1);
    }

    public function loadrow($config)
    {
        $access = $this->othersClass->checkAccess($config['params']['user'], 5466);
        if ($access) {
            $regline = $config['params']['classid']['regline'];
            $qry = "select e.empid, e.trno as line, concat(emp.empfirst, ' ', emp.emplast) as name, e.dateevaluated as dateid, '' as bgcolor from cmevaluate as e left join employee as emp on emp.empid=e.empid where e.trno=" . $regline;
            return $this->coreFunctions->opentable($qry);
        }
        return [];
        // $empid = $config['params']['classid']['empid'];
        // $regid = $config['params']['classid']['regid'];

        // $classid = $config['params']['classid']['stat'];


        // $data = $this->coreFunctions->opentable("select client.clientname as empname,reg.regid,reg.empid,date(reg.reaccess) as reaccess,
        //             date(reg.evaluated) as evaluated,'" . $classid . "' as stat
        // from regprocess as reg
        // left join client on client.clientid=reg.empid
        // where reg.empid=$empid and reg.regid=$regid");

        // return $data[0];
    }

    public function loaddata($config)
    {
        $row = $config['params']['dataparams'];
        $userid = $config['params']['adminid'];
        $user = $config['params']['user'];
        $stat = $config['params']['dataparams']['stat'];

        $checkevaluator = $this->coreFunctions->opentable("select trno from cmevaluate where trno=" . $row['line'] . " and dateevaluated is null");
        if (!empty($checkevaluator)) {
            return ['status' => false, 'msg' => 'Unable to tag EVALUATED, there are existing evaluators not yet been done.'];
        }
        if ($stat == 'E') {
            $data['reaccess'] = $this->othersClass->getCurrentTimeStamp();
        } else {
            $data = [
                'evaluated' => $this->othersClass->getCurrentTimeStamp(),
                'evaluatedby' => $userid
            ];
        }
        $result = $this->coreFunctions->sbcupdate("regprocess", $data, ['regid' =>  $row['regid'], 'empid' =>  $row['empid']]);

        if ($result) {

            if ($stat == 'P') {
                $sort = $this->coreFunctions->getfieldvalue('regularization', 'sortline', 'line=?', [$row['regid']]);
                $sort = $sort + 1;

                $chksortline = $this->coreFunctions->getfieldvalue('regularization', 'line', 'sortline=?', [$sort]);

                if (!empty($chksortline)) {
                    $reg = $this->coreFunctions->opentable("select reg.sortline as sort,reg.line
                        from regularization as reg
                        left join regprocess as rp on rp.regid=reg.line
                        where reg.sortline = $sort and empid='" . $row['empid'] . "'", [], '', true);

                    if (empty($reg)) {
                        $datehire = $this->coreFunctions->getfieldvalue('employee', 'hired', 'empid=?', [$row['empid']]);
                        $regid = $this->coreFunctions->getfieldvalue('regularization', 'line', 'sortline=?', [$sort]);
                        $regprocess = $this->coreFunctions->opentable("select line, adddate('" . $datehire . "', interval num day) 
                                as expiration from regularization 
                                where isdays=1 and isinactive=0 and line= $regid", [], '', true);

                        if (!empty($regprocess)) {
                            foreach ($regprocess as $key => $value) {

                                $datareg = [
                                    'regid' => $regid,
                                    'empid' => $row['empid'],
                                    'expiration' => $value->expiration,
                                    'createby' => $user,
                                    'createdate' => $this->othersClass->getCurrentTimeStamp()
                                ];

                                $this->coreFunctions->sbcinsert("regprocess", $datareg);
                            }
                        }
                    }
                } else {
                    $r = 'Regular';
                    $chkempstat = $this->coreFunctions->getfieldvalue('empstatentry', 'line', 'empstatus=?', [$r]);
                    $regular = $this->othersClass->getCurrentTimeStamp();
                    if (!empty($chkempstat)) {
                        $this->coreFunctions->sbcupdate("employee", ['empstatus' => $chkempstat, 'regular' => $regular], ['empid' => $row['empid']]);
                    } else {
                        return ['status' => false, 'msg' => 'Failed to save.', 'reloadlist' => true, 'closecustomform' => true];
                    }
                }
            }
        }

        return ['status' => true, 'msg' => 'Successfully saved.', 'reloadlist' => true, 'closecustomform' => true];
    }

    public function stockstatus($config)
    {
        $row = $config['params']['row'];
        $check = $this->coreFunctions->datareader("select trno as value from cmevaluate where trno=" . $row['line'] . " and empid=" . $row['empid'] . " and dateevaluated is not null", [], '', true);
        if ($check != 0) return ['status' => false, 'msg' => 'Cannot remove Evaluator, already evaluated'];
        if ($this->coreFunctions->execqry("delete from cmevaluate where trno=" . $row['line'] . " and empid=" . $row['empid'], 'delete')) {
            $this->coreFunctions->execqry("delete from pendingapp where line=" . $row['line'] . " and clientid=" . $row['empid'] . " and doc='CONTRACTMONITORING'", 'delete');
            $data = $this->coreFunctions->opentable("select e.empid, e.trno as line, concat(emp.empfirst, ' ', emp.emplast) as name from cmevaluate as e left join employee as emp on emp.empid=e.empid where e.trno=" . $row['line']);
            return ['status' => true, 'msg' => 'Evaluator removed', 'griddata' => $data];
        }
        return ['status' => false, 'msg' => 'Error removing Evaluator'];
    }

    public function execute()
    {
        return response()->json($this->config['return'], 200);
    } // end function
} //end class
