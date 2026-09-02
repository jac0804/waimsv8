<?php

namespace App\Http\Classes\modules\payrollentry;

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
use App\Http\Classes\sbcdb\trigger;
use App\Http\Classes\sbcdb\waims;
use App\Http\Classes\sbcdb\customersupport;

class viewreimbursement
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'REIMBERSEMENT LIST';
    public $gridname = 'customformacctg';
    private $companysetup;
    private $coreFunctions;
    public $tablenum = 'cntnum';
    private $othersClass;
    public $head = 'lahead';
    public $hhead = 'glhead';
    public $detail = 'ladetail';
    public $hdetail = 'gldetail';
    public $tablelogs = 'table_log';
    public $htablelogs = 'htable_log';
    public $tablelogs_del = 'del_table_log';
    public $style = 'width:50%;max-width:20%;';
    public $issearchshow = false;
    public $showclosebtn = true;
    private $logger;
    public $contra = 'APV';
    public $fields = ['trno', 'client', 'jono', 'amount'];
    private $acctg = [];

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
    public function createHeadField($config)
    {
        return [];
    }
    public function createTab($config)
    {

        $cols = ['clientname', 'createdate', 'amount', 'jono', 'rem', 'empid', 'ispicked'];
        foreach ($cols as $key => $value) {
            $$value = $key;
        }
        $stockbuttons = [];
        $tab = [$this->gridname => ['gridcolumns' => $cols]];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = "label";
        // $obj[0][$this->gridname]['columns'][$action]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = "width:350px;whiteSpace: normal;min-width:350px;";
        $obj[0][$this->gridname]['columns'][$clientname]['label'] = "Employee Name";

        $obj[0][$this->gridname]['columns'][$amount]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$amount]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$jono]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$jono]['style'] = "text-align:left;width:150px;whiteSpace: normal;min-width:150px;";

        $obj[0][$this->gridname]['columns'][$rem]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$rem]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";

        $obj[0][$this->gridname]['columns'][$empid]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$empid]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][$ispicked]['label'] = "Select";


        $obj[0][$this->gridname]['columns'][$createdate]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$createdate]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        return $obj;
    }
    public function createtabbutton($config)
    {
        $tbuttons = ['saveallentry'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[0]['label'] = "GENERATE";
        $obj[0]['lookupclass'] = "closeform";
        return $obj;
    }
    public function loaddata($config)
    {

        $row = $config['params']['row'];

        if (isset($row['rtrno'])) {
            $trno =  $row['rtrno'] != "" ? $row['rtrno'] : 0;
        } else {
            $row = $config['params']['sourcerow'];
            $trno =  $row['rtrno'] != "" ? $row['rtrno'] : 0;
        }

        $qry = "select task.amt as amount,client.client,client.clientname,task.userid,task.jono,'' as bgcolor,'' as errcolor,'false' as ispicked,task.trno,date(task.createdate) as createdate,task.rem,task.clientid as customerid from hdailytask as task
                    left join client on client.clientid = task.userid
                    where task.userid = " . $row['clientid'] . " and task.trno in ( $trno ) and task.apvtrno = 0";
        return $this->coreFunctions->opentable($qry);
    }
    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        $status = true;
        $hdtrno_list = [];
        $apprefx = [];;
        $trno = 0;
        $docno = "";
        $userid = 0;

        $acc_data = ['client' => '', 'amount' => 0, 'line' => 0];
        $dateTables = ['lahead','ladetail', 'glhead', 'gldetail', 'hdailytask'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $config['params']['companyid'], [], false, $dateTables);

        foreach ($data as $key => $value) {
            $data2 = [];
            $msg = "";
            if ($data[$key]['bgcolor'] != '' && $data[$key]['ispicked'] == 'true') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfieldFast($value2, $data[$key][$value2],$lookups);
                }
                // check existing head
                array_push($hdtrno_list, $data[$key]['trno']);
                $userid = $data[$key]['userid'];

                $headdata = $this->coreFunctions->opentable("select trno,docno from lahead where doc = 'PV' and client ='" . $data[$key]['client'] . "'");

                $alias = $this->coreFunctions->datareader("select case when alias <> '' then alias else clientname end as value
                from client where clientid = ". $data[$key]['customerid']."");

                $date = $this->othersClass->getCurrentDate();
                $cur = $this->companysetup->getdefaultcurrency($config['params']);
                $forex = 1;
                if (!empty($headdata)) {
                    $docno = $headdata[0]->docno;
                    $trno = $headdata[0]->trno;
                    $config['params']['doc'] = 'PV';
                } else {
                    //generate header
                    $doc = 'PV';
                    $pvref = 'PV';
                    $apvref = 'APV';
                    $table = 'cntnum';
                    $center = $config['params']['center'];
                    $docnolength =  $this->companysetup->getdocumentlength($config['params']);

                    $getdoc = $this->coreFunctions->getfieldvalue($table, 'doc', 'bref=?', [$apvref]);
                    $seq = $this->othersClass->getlastseq($apvref, $config, $table);
                    if ($getdoc == '') {
                        $seq = $this->othersClass->getlastseq($pvref, $config, $table);
                    }
                    $mrseq = $apvref . $seq;
                    $newdocno = $this->othersClass->PadJ($mrseq, $docnolength);
                    $col = ['doc' => $doc, 'docno' => $newdocno, 'seq' => $seq, 'bref' => $apvref, 'center' => $center];
                    $insert = $this->coreFunctions->insertGetId($table, $col);

                    $cntdata =  $this->coreFunctions->opentable("select trno,docno from cntnum where doc = ? and docno = ? and center = ?", [$doc, $newdocno, $center]);

                    $docno = $cntdata[0]->docno;
                    $trno = $cntdata[0]->trno;
                    $this->coreFunctions->logconsole($trno . '---' . $docno);

                    $head = [
                        'trno' => $trno,
                        'doc' => $doc,
                        'docno' => $docno,
                        'client' => $data[$key]['client'],
                        'clientname' => $data[$key]['clientname'],
                        'cur' => $cur,
                        'forex' => $forex,
                        'dateid' => $date,
                        'rem' => 'REIMBURSEMENT',
                    ];
                    foreach ($head as $key2 => $val) {
                        $head[$key2] = $this->othersClass->sanitizekeyfieldFast($key2, $head[$key2], $lookups);
                    }
                    $head['createdate'] = $date;
                    $head['createby'] = $config['params']['user'];
                    $lahead = $this->coreFunctions->sbcinsert('lahead', $head);
                    $config['params']['doc'] = $doc;

                    if ($lahead) {
                        $msg = ' New Transaction Generated ' . $docno . '<br> ';
                        $this->logger->sbcwritelog($trno, $config, 'CREATE', ' REIMBURSEMENT - ' . $docno . ' - ' . $data[$key]['client'] . ' - ' . $data[$key]['clientname']);
                    } else {
                        $msg .= ' Creating Head Failed. ';
                        $this->logger->sbcwritelog($trno, $config, 'CREATE', $msg);
                        $status = false;
                        break;
                    }
                }
                //details

                $qry = "select line as value from ladetail where trno = ? order by line desc limit 1";
                $line = $this->coreFunctions->datareader($qry, [$trno]);
                if ($line == '') {
                    $line = 0;
                }
                $line = $line + 1;
                $qry = "select reimbursement,rem from hdailytask where refx = ?";

                $type = $this->coreFunctions->opentable($qry, [$data[$key]['trno']]);
                $acnoid = $this->coreFunctions->datareader("select acnoid as value from coa where acnoname like '%". $type[0]->reimbursement."%' limit 1");
                $jono ='';
                if($data[$key]['jono'] != ""){
                   $jono =  ' - '.$data[$key]['jono'];
                }
                $details = [
                    'trno' => $trno,
                    'line' => $line,
                    'client' => $data[$key]['client'],
                    'rem' => $alias . ' - '. $type[0]->rem . $jono,
                    'cr' => 0,
                    'db' => $data[$key]['amount'],
                    'damt' => $data[$key]['amount'],
                    'forex' => $forex,
                    'cur' => $cur,
                    'sortline' => $line,
                    'postdate' => $data[$key]['createdate'],
                    'acnoid' => $acnoid


                ];
                foreach ($details as $key3 => $val2) {
                    $details[$key3] = $this->othersClass->sanitizekeyfieldFast($key3, $details[$key3], $lookups);
                }
                $date2 = $this->othersClass->getCurrentTimeStamp();
                $details['encodeddate'] = $date2;
                $details['encodedby'] = $config['params']['user'];
                $ladetail = $this->coreFunctions->sbcinsert('ladetail', $details);

                $qry = "select trno as value from hdailytask where refx = ? limit 1";
                $hdtrno = $this->coreFunctions->datareader($qry, [$data[$key]['trno']], '', true);
                array_push($apprefx, $hdtrno);
                $this->coreFunctions->logconsole($hdtrno . '-' . $trno);
                if ($ladetail) {
                    $msg = ' Details Inserted Successfully.';
                    $apvtrno = $this->coreFunctions->execqry("update hdailytask set apvtrno = $trno where userid =?  and  trno =? ", 'update', [$userid, $data[$key]['trno']]);
                    $this->logger->sbcwritelog($trno, $config, 'DETAIL', ' ADD ' . 'Line : ' . $line . ' CR: ' . $data[$key]['amount'] . ' Notes: AUTO GENERATED REIMBURSEMENT');
                    if ($apvtrno) {
                        $acc_data['amount'] += $data[$key]['amount'];
                        $acc_data['line'] = $line;
                        $acc_data['client'] = $data[$key]['client'];
                    }
                } else {
                    // failed to generate head or insert details
                    $msg = ' Insert Details Failed.';
                    $this->logger->sbcwritelog($trno, $config, 'DETAIL', $msg);
                    $status = false;
                    break;
                }
                $details = [];
            }
        }
        $apptrno = implode(",", $apprefx);
        $pendingdata = $this->coreFunctions->opentable("select trno,line,doc,clientid,approver from pendingapp where trno in ($apptrno) and doc = 'DY' and approver = 'REIMBURSEMENT'");
        if ($status) {
            $config['params']['trno'] = $trno;
            $distribution = $this->createdistribution($config, $acc_data);
            if ($distribution) {
                $posttran = $this->othersClass->posttransacctg($config);
                $this->coreFunctions->logconsole($posttran['msg'] . ' status: ' . $posttran['status']);
                if (!$posttran['status']) {
                    $msg = $posttran['msg'];
                    goto endgenerate;
                } else {
                    $msg = $posttran['msg'];
                    $this->coreFunctions->execqry("delete from pendingapp where trno in ($apptrno) and approver = ?", 'delete', ['REIMBURSEMENT']);
                }
            } else {
                $msg = ' Automatic Distribution Failed.';
                goto endgenerate;
            }
        } else {
            endgenerate:
            $this->coreFunctions->LogConsole('Pendingapp trno: ' . $apptrno);
            $this->coreFunctions->execqry("delete from pendingapp where trno in ($apptrno) and doc = 'DY' and approver = ?", 'delete', ['REIMBURSEMENT']);

            if (!empty($pendingdata)) {
                foreach ($pendingdata as $pd) {
                    $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid,approver) values(?, ?, ?, ? , ?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                }
            }
            $hdtrno_list = implode(",", $hdtrno_list);
            $this->coreFunctions->execqry("update hdailytask set apvtrno = 0 where userid = " . $userid . " and apvtrno = $trno and  trno in ($hdtrno_list)", 'update');
            $this->coreFunctions->execqry("delete from lahead where trno=? and doc = 'PV'", 'delete', [$trno]);
            $this->coreFunctions->execqry("delete from ladetail where trno=?", 'delete', [$trno]);
            $this->coreFunctions->execqry('delete from cntnum where trno=?', 'delete', [$trno]);
            $this->logger->sbcdel_log($trno, $config, $docno);
        }



        $returndata = $this->loaddata($config);
        $allreimbursedata = $this->getallreimbursement($config);
        return ['status' => $status, 'msg' => $msg, 'data' => $returndata, 'reloadtableentry' => $allreimbursedata, 'getsbclistdata' => ['gapplications']]; // close this form and reloadtableentry na unang tinawag bago itong form na ito 
    }
    public function getallreimbursement($config)
    {
        $trno_list = [];

        $query = "select refx as trno from hdailytask as task 
		left join pendingapp as app on app.trno = task.trno
		where app.approver = 'REIMBURSEMENT' and app.doc = 'DY'";
        $data_list = $this->coreFunctions->opentable($query); // list of reimbursement

        if (!empty($data_list)) {
            foreach ($data_list as $refx) {
                array_push($trno_list, $refx->trno);
            }
            $trno_list = array_unique($trno_list);
            $trno = implode(",", $trno_list);


            $qry = "select sum(task.amt) as amount,client.clientname,count(task.trno) as appcount,task.userid as clientid,'$trno' as rtrno from hdailytask as task 
                    left join client on client.clientid = task.userid
                    where task.statid = 1 and task.trno in ( $trno ) and task.apvtrno = 0
		            group by client.clientname,task.userid";
            return $this->coreFunctions->opentable($qry);
        } else {
            return [];
        }
    }

    public function createdistribution($config, $data)
    {
        $entry = [];
        $trno = $config['params']['trno'];
        $status = true;
        $postdate = $this->othersClass->getCurrentDate();
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $data['line'] += 1;
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', [$this->contra]);
        $entry = ['acnoid' => $acnoid, 'client' => $data['client'],  'ref' => '', 'db' => 0, 'cr' => $data['amount'], 'postdate' => $postdate, 'line' => $data['line'], 'sortline' => $data['line']];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

        $dateTables = ['ladetail'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $config['params']['companyid'], [], false, $dateTables);

        foreach ($this->acctg as $key => $value) {
            foreach ($value as $key2 => $value2) {
                $this->acctg[$key][$key2] = $this->othersClass->sanitizekeyfieldFast($key2, $value2,$lookups);
            }
            $this->acctg[$key]['encodeddate'] = $current_timestamp;
            $this->acctg[$key]['encodedby'] = $config['params']['user'];     
            $this->acctg[$key]['trno'] = $trno;
            $this->acctg[$key]['db'] = round($this->acctg[$key]['db'], 2);
            $this->acctg[$key]['cr'] = round($this->acctg[$key]['cr'], 2);
            $this->acctg[$key]['sortline'] = $data['line'];
        }
        $this->coreFunctions->LogConsole(json_encode($this->acctg));
        if ($this->coreFunctions->sbcinsert('ladetail', $this->acctg) == 1) {
            $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION SUCCESS');
            $status = true;
        } else {
            $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION FAILED');
            $status = false;
        }
        return $status;
    }
} //end class
