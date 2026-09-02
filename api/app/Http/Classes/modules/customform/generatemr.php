<?php

namespace App\Http\Classes\modules\customform;

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
use DateTime;

class generatemr
{
    private $fieldClass;
    private $tabClass;
    private $companysetup;
    private $coreFunctions;
    public $modulename = 'List of Customers with Retainer Fees';
    public $gridname = 'inventory';
    public $head = 'lahead';
    public $hhead = 'glhead';
    public $stock = 'lastock';
    public $hstock = 'glstock';
    public $detail = 'ladetail';
    public $hdetail = 'gldetail';
    public $tablenum = 'cntnum';
    public $tablelogs = 'table_log';
    public $htablelogs = 'htable_log';
    public $tablelogs_del = 'del_table_log';
    private $othersClass;
    public $style = 'width:20%;max-width:40%;height:20%;max-height:40%;';
    public $showclosebtn = true;
    public $issearchshow = false;
    // public $fields = [];
    public $except = ['dateid'];
    public $logger;



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
        $fields = ['dateid'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dateid.readonly', false);

        $fields = ['generatemr'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'generatemr.style', 'width:100%;');
        $fields = ['generateyb'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'generateyb.style', 'width:100%;');
        $fields = [];
        $col4 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }
    
    public function createTab($config)
    {
        $cols = [];
        $tab = [
            $this->gridname => [
                'gridcolumns' => $cols
            ]
        ];

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
        return $this->coreFunctions->opentable("select left(now(),10) as dateid");
    }
    public function getheaddata($config)
    {
        return [];
    }
    public function loaddata($config)
    {
        $action = $config['params']['action2'];
        $generateddata = '';
        $qry = "select * from client where charge1 <> 0";
        $data = $this->coreFunctions->opentable($qry);
        switch ($action) {
            case 'generatemr':
                return $this->generatemr($config);
                break;
            case 'generateyb':
                return $this->generateyb($config);
                break;
        }
    }
    public function data()
    {
        return [];
    }
    // public function generatemrold($config)
    // {
    //     $companyid = $config['params']['companyid'];
    //     $dateid = $config['params']['dataparams']['dateid'];
    //     $date = new DateTime($dateid);
    //     $qry = "select clientid,client,clientname,addr,terms,charge1,tax,vattype from client where charge1 <> 0 and iscustomer = 1 order by clientname";
    //     $data = $this->coreFunctions->opentable($qry);
    //     $msg = '';
    //     $msg2 = '';
    //     $trno = 0;
    //     $status = true;
    //     if (!empty($data)) {
    //         foreach ($data as $key => $value) {

    //             $doc = 'SJ';
    //             $sjref = 'SJ';
    //             $mrref = 'MR';
    //             $table = 'cntnum';
    //             $center = $config['params']['center'];
    //             $isgenerate = $this->isprgenerated($config, $data[$key]->clientid, $date->format('Y-m'), $doc);
    //             $insert = 0;
    //             if (!$isgenerate) {
    //                 while ($insert == 0) {
    //                     $docnolength =  $this->companysetup->getdocumentlength($config['params']);
    //                     //get seq ng sj then check if my mr doc na then use mr else get sj seq
    //                     $getdoc = $this->coreFunctions->getfieldvalue($table, 'doc', 'bref=?', [$mrref]);
    //                     $seq = $this->othersClass->getlastseq($mrref, $config, $table);
    //                     if ($getdoc == '') {
    //                         $seq = $this->othersClass->getlastseq($sjref, $config, $table);
    //                     }
    //                     $mrseq = $mrref . $seq;
    //                     $newdocno = $this->othersClass->PadJ($mrseq, $docnolength);
    //                     $col = ['doc' => $doc, 'docno' => $newdocno, 'seq' => $seq, 'bref' => $mrref, 'center' => $center];
    //                     $insert = $this->coreFunctions->insertGetId($table, $col);
    //                 }
    //             } else {
    //                 continue;
    //             }
    //             $qry = "select trno,docno from cntnum where doc = ? and docno = ? and center = ? ";
    //             $data2 =  $this->coreFunctions->opentable($qry, [$doc, $newdocno, $center]);
    //             if (!empty($data2)) {
    //                 $trno =  $data2[0]->trno;
    //                 $docno = $data2[0]->docno;
    //                 $this->coreFunctions->logconsole($trno . '---' . $docno);
    //                 $user = $config['params']['user'];
    //                 $center = $config['params']['center'];
    //                 //  insert headdata 
    //                 $defcontra = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['AR1']);
    //                 $contra = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$defcontra]);

    //                 $wh = $this->coreFunctions->getfieldvalue("center", "warehouse", "code=?", [$center]);
    //                 $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
    //                 $cur = $this->companysetup->getdefaultcurrency($config['params']);
    //                 //get all default for creating new trans
    //                 $datenow = $this->othersClass->getCurrentTimeStamp();
    //                 $forex = 1;
    //                 $datahead = [
    //                     'trno' => $trno,
    //                     'doc' => $doc,
    //                     'docno' => $docno,
    //                     'client' => $data[$key]->client,
    //                     'clientname' => $data[$key]->clientname,
    //                     'address' => $data[$key]->addr,
    //                     'dateid' => $date->format('Y-m-d'),
    //                     'due' => $date->format('Y-m-d'),
    //                     'cur' => $cur,
    //                     'contra' => $defcontra,
    //                     'wh' => $wh,
    //                     'forex' => $forex,
    //                     'deldate' => $date->format('Y-m-d'),
    //                     'tax' => $data[$key]->tax,
    //                     'vattype' => $data[$key]->vattype,
    //                     'rem' => "Service Fee for the month of " . date("F", strtotime($date->format('Y-m-d'))) . " " . date('Y', strtotime($date->format('Y-m-d')))
    //                 ];

    //                 $dateTables = ['lahead', 'lastock'];
    //                 $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);
    //                 foreach ($datahead as $key2 => $val) {
    //                     if (!in_array($key, $this->except)) {
    //                         $datahead[$key2] = $this->othersClass->sanitizekeyfieldFast($key2, $datahead[$key2], $lookups);
    //                     } //end if
    //                 }
    //                 $datahead['createdate'] = $datenow;
    //                 $datahead['createby'] = $config['params']['user'];
    //                 $insert = $this->coreFunctions->sbcinsert($this->head, $datahead);
    //                 // stock insert data from item
    //                 // itemid = 2004 testing change nalang
    //                 if ($insert) {
    //                     $qry = "select itemid,itemname,uom from item where itemid = 18";
    //                     $isnoninv =  $this->coreFunctions->opentable($qry);
    //                     if (!empty($isnoninv)) {
    //                         $disc = '';
    //                         $qty = 1;
    //                         $kgs = 0;
    //                         $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
    //                         $item = $this->coreFunctions->opentable($qry, [$isnoninv[0]->uom, $isnoninv[0]->itemid]);
    //                         $factor = 1;
    //                         if (!empty($item)) {
    //                             $item[0]->factor = $this->othersClass->val($item[0]->factor);
    //                             if ($item[0]->factor !== 0) $factor = $item[0]->factor;
    //                         }

    //                         $amt = $data[$key]->charge1;
    //                         $amt = $this->othersClass->sanitizekeyfieldFast('amt', $amt, $lookups);
    //                         $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));
    //                         $qty = $this->othersClass->sanitizekeyfieldFast('qty', $qty, $lookups);
    //                         $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, $cur, $kgs);
    //                         $hamt = $computedata['amt'] * $forex;
    //                         $hamt = $this->othersClass->sanitizekeyfieldFast('amt', $hamt, $lookups);

    //                         $ext = number_format($qty * $amt, 2);
    //                         $hamt = number_format($hamt, 2);

    //                         $qry = "select line as value from $this->stock where trno= ? order by line desc limit 1";
    //                         $line = $this->coreFunctions->datareader($qry, [$trno]);
    //                         if ($line == '') {
    //                             $line = 0;
    //                         }
    //                         $line = $line + 1;
    //                         $datastock = [
    //                             'trno' => $trno,
    //                             'line' => $line,
    //                             'refx' => 0,
    //                             'linex' => 0,
    //                             'itemid' => $isnoninv[0]->itemid,
    //                             'uom' => $isnoninv[0]->uom,
    //                             'isamt' => $hamt,
    //                             'amt' => $amt,
    //                             'isqty' => $qty,
    //                             'iss' => $computedata['qty'],
    //                             'ext' => $ext,
    //                             'cost' => 0,
    //                             'kgs' => $kgs,
    //                             'disc' => $disc,
    //                             'whid' => $whid,
    //                         ];
    //                         foreach ($datastock as $key2 => $val) {
    //                             $datastock[$key2] = $this->othersClass->sanitizekeyfieldFast($key2, $datastock[$key2], $lookups);
    //                         }
    //                         $datastock['encodeddate'] = $datenow;
    //                         $datastock['encodedby'] = $user;
    //                         $insertstock = $this->coreFunctions->sbcinsert($this->stock, $datastock);

    //                         if ($insertstock) {
    //                             $path = 'App\Http\Classes\modules\sales\sj';
    //                             $config['params']['clientid'] = $trno;
    //                             $config['params']['trno'] = $trno;
    //                             $return = app($path)->posttrans($config);
    //                             if ($return['status']) {
    //                                 $msg .= 'New Transaction Generated ' . $newdocno;
    //                             } else {
    //                                 $msg2 .= $return['msg'];
    //                                 goto delete;
    //                             }
    //                         } else {
    //                             $msg2 .= 'Failed to insert stock ' . $data[$key]->client;
    //                             goto delete;
    //                         }
    //                     }
    //                 } else {
    //                     delete:
    //                     $msg2 .= 'Failed to insert header ' . $data[$key]->client;
    //                     $qry = 'delete from cntnum where trno=?';
    //                     $this->coreFunctions->execqry($qry, 'delete', [$trno]);
    //                     $qry = 'delete from lastock where trno=?';
    //                     $this->coreFunctions->execqry($qry, 'delete', [$trno]);
    //                     $qry = 'delete from lahead where trno=?';
    //                     $this->coreFunctions->execqry($qry, 'delete', [$trno]);
    //                 }
    //             }
    //         }
    //         if ($status) {
    //             if ($msg2 != '') {
    //                 return ['status' => false, 'msg' => $msg . '</br>' . $msg2];
    //             } else {
    //                 if ($msg != '') {
    //                     return ['status' => $status, 'msg' => $msg . ' ' . $msg2];
    //                 } else {
    //                     return ['status' => $status, 'msg' => 'No customer found with retainers fee this month'];
    //                 }
    //             }
    //         }
    //     } else {
    //         return ['status' => false, 'msg' => 'No Customer Found with Retainer Fee'];
    //     }
    // }

    public function isprgenerated($config, $clientid, $dateid, $doc, $billtype = '')
    {
        $document = $this->coreFunctions->datareader(
            "select gd.trno as value
            from gldetail as gd
            left join glhead as gh on gh.trno = gd.trno
            where gd.clientid = ?
            and date_format(gh.dateid, '%Y-%m') = ?
            and gh.doc = ? and left(gh.docno,2)='MR' and gh.ourref = ?
            limit 1",
            [$clientid, $dateid, $doc, $billtype]
        );

        if ($document === '' || $document === null) {
            return false;
        } else {
            return true;
        }
    } // end function

    public function generatemr($config)
    {
        $companyid = $config['params']['companyid'];
        $dateid = $config['params']['dataparams']['dateid'];
        $date = new DateTime($dateid);
        $monthYear = $date->format('F Y');

        $doc = 'SJ';
        $center = $config['params']['center'];

        //Get billtypes that exist for MONTHLY billing
        $billtypes = $this->coreFunctions->opentable(
            "select distinct billtype from billingmaster where sched = 0"
        );

        if (empty($billtypes)) {
            return ['status' => false, 'msg' => 'No monthly bill types found in billing setup'];
        }

        $generatedDocs = [];
        $failedDocs = [];
        $msg2 = '';

        // Loop through each billtype
        foreach ($billtypes as $bt) {
            $billtype = $bt->billtype;

            $qry = "select c.clientid, c.client, c.clientname, c.addr, c.tax, c.vattype,
    cb.amt, cb.isvat, bm.billtype, bm.sched,
    ca.acno as ar_account, cr.acno as revenue_account,
    ca.acnoid as ar_acnoid, cr.acnoid as revenue_acnoid,
    cb.bline
    from clbilling as cb
    left join billingmaster as bm on bm.line = cb.bline
    left join client as c on c.clientid = cb.clientid
    left join coa as ca on ca.acnoid = bm.assetid
    left join coa as cr on cr.acnoid = bm.revenueid
    where cb.amt <> 0 and c.iscustomer = 1
    and bm.sched = 0 and bm.billtype = ?
    order by c.clientname";

            $data = $this->coreFunctions->opentable($qry, [$billtype]);

            if (empty($data)) {
                $msg2 .= 'No customers with ' . $billtype . ' billing setup found.<br>';
                continue;
            }

            $eligibleLines = [];
            foreach ($data as $row) {
                $isgenerate = $this->isprgenerated($config, $row->clientid, $date->format('Y-m'), $doc, $billtype);
                if (!$isgenerate) {
                    $eligibleLines[] = $row;
                }
            }

            if (empty($eligibleLines)) {
                $msg2 .= 'All ' . count($data) . ' customer(s) with ' . $billtype . ' already generated this month.<br>';
                continue;
            }

            $result = $this->generatemrdoc($config, $billtype, $eligibleLines, $date, $monthYear);

            if ($result['status']) {
                $generatedDocs[] = $result['docno'] . ' (' . $billtype . ')';
            } else {
                $failedDocs[] = $billtype;
                $msg2 .= $result['msg'] . ' for ' . $billtype . '<br>';
            }
        } // end foreach billtype

        if (!empty($generatedDocs)) {
            $resultMsg = 'MR Documents Generated: ' . implode(', ', $generatedDocs);
            if ($msg2 != '') {
                $resultMsg .= '<br>' . $msg2;
            }
            return ['status' => true, 'msg' => $resultMsg];
        } else {
            if ($msg2 != '') {
                return ['status' => false, 'msg' => 'MR Generation Failed: ' . $msg2];
            }
            return ['status' => false, 'msg' => 'No new customers found for MR generation this month'];
        }
    } // end function

    private function generatemrdoc($config, $billtype, $eligibleLines, $date, $monthYear)
    {
        $companyid = $config['params']['companyid'];
        $doc = 'SJ';
        $sjref = 'SJ';
        $mrref = 'MR';
        $table = 'cntnum';
        $center = $config['params']['center'];
        $docnolength = $this->companysetup->getdocumentlength($config['params']);
        $user = $config['params']['user'];
        $cur = $this->companysetup->getdefaultcurrency($config['params']);
        $datenow = $this->othersClass->getCurrentTimeStamp();
        $forex = 1;
        $defcontra = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['AR1']);
        $defcontra_acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['AR1']);
        $wh = $this->coreFunctions->getfieldvalue("center", "warehouse", "code=?", [$center]);
        $dateTables = ['lahead', 'ladetail'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);
        $newdocno = '';
        $trno = 0;

        // reserve doc number
        $insert = 0;
        $attempts = 0;
        while ($insert == 0) {
            $attempts++;
            if ($attempts > 20) {
                return ['status' => false, 'msg' => 'Unable to reserve a document number after multiple attempts'];
            }
            $getdoc = $this->coreFunctions->getfieldvalue($table, 'doc', 'bref=?', [$mrref]);
            $seq = $this->othersClass->getlastseq($mrref, $config, $table);
            if ($getdoc == '') {
                $seq = $this->othersClass->getlastseq($sjref, $config, $table);
            }
            $mrseq = $mrref . $seq;
            $newdocno = $this->othersClass->PadJ($mrseq, $docnolength);
            $col = ['doc' => $doc, 'docno' => $newdocno, 'seq' => $seq, 'bref' => $mrref, 'center' => $center];
            $insert = $this->coreFunctions->insertGetId($table, $col);
        }

        $qry = "select trno,docno from cntnum where doc = ? and docno = ? and center = ? ";
        $data2 = $this->coreFunctions->opentable($qry, [$doc, $newdocno, $center]);

        if (empty($data2)) {
            return ['status' => false, 'msg' => 'Failed to generate document number'];
        }

        $trno = $data2[0]->trno;
        $docno = $data2[0]->docno;

        $customerAccounts = [];
        foreach ($eligibleLines as $customer) {
            $arAcnoid = $customer->ar_acnoid ?: $defcontra_acnoid;
            $revAcnoid = $customer->revenue_acnoid ?: $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['SA1']);
            $customerAccounts[] = [
                'client' => $customer->client,
                'ar_acnoid' => $arAcnoid,
                'rev_acnoid' => $revAcnoid,
                'amt' => $customer->amt,
                'isvat' => $customer->isvat,
                'bline' => $customer->bline
            ];
        }

        $remlabel = "Service Fee for " . $billtype . " for the month of " . $monthYear;

        $datahead = [
            'trno' => $trno,
            'doc' => $doc,
            'docno' => $docno,
            'client' => '',
            'clientname' => '',
            'address' => '',
            'ourref' => $billtype,
            'dateid' => $date->format('Y-m-d'),
            'due' => $date->format('Y-m-d'),
            'cur' => $cur,
            'contra' => $defcontra,
            'wh' => $wh,
            'forex' => $forex,
            'deldate' => $date->format('Y-m-d'),
            'tax' => 0,
            'vattype' => '',
            'rem' => $remlabel
        ];

        foreach ($datahead as $key2 => $val) {
            if (!in_array($key2, $this->except)) {
                $datahead[$key2] = $this->othersClass->sanitizekeyfieldFast($key2, $datahead[$key2], $lookups);
            }
        }
        $datahead['createdate'] = $datenow;
        $datahead['createby'] = $user;
        $insert = $this->coreFunctions->sbcinsert($this->head, $datahead);

        if (!$insert) {
            $this->coreFunctions->execqry('delete from cntnum where trno=?', 'delete', [$trno]);
            return ['status' => false, 'msg' => 'Failed to create header'];
        }

        $detailLine = 0;
        $detailData = [];
        $totalAmount = 0;

        foreach ($eligibleLines as $customer) {
            $arAcnoid = $customer->ar_acnoid ?: $defcontra_acnoid;
            $detailLine++;
            $totalAmount += $customer->amt;

            $detailRow = [
                'trno' => $trno,
                'line' => $detailLine,
                'client' => $customer->client,
                'rem' => $remlabel,
                'db' => $customer->amt,
                'cr' => 0,
                'fdb' => $customer->amt,
                'fcr' => 0,
                'forex' => $forex,
                'agent' => '',
                'postdate' => $date->format('Y-m-d'),
                'ref' => '',
                'checkno' => '',
                'duedate' => $date->format('Y-m-d'),
                'refx' => 0,
                'linex' => 0,
                'clearday' => null,
                'encodeddate' => $datenow,
                'encodedby' => $user,
                'isvat' => $customer->isvat,
                'damt' => $customer->amt,
                'cur' => $cur,
                'acnoid' => $arAcnoid,
                'void' => 0,
                'deptid' => 0,
                'branch' => 0,
                'agentid' => 0
            ];

            foreach ($detailRow as $key2 => $val) {
                $detailRow[$key2] = $this->othersClass->sanitizekeyfieldFast($key2, $detailRow[$key2], $lookups);
            }

            $detailData[] = $detailRow;
        }

        if (empty($detailData)) {
            $this->coreFunctions->execqry('delete from cntnum where trno=?', 'delete', [$trno]);
            $this->coreFunctions->execqry('delete from lahead where trno=?', 'delete', [$trno]);
            return ['status' => false, 'msg' => 'No valid customer details to insert'];
        }

        $insertDetails = $this->coreFunctions->sbcinsert($this->detail, $detailData);

        if (!$insertDetails) {
            $this->coreFunctions->execqry('delete from cntnum where trno=?', 'delete', [$trno]);
            $this->coreFunctions->execqry('delete from lahead where trno=?', 'delete', [$trno]);
            $this->coreFunctions->execqry('delete from ladetail where trno=?', 'delete', [$trno]);
            return ['status' => false, 'msg' => 'Failed to insert customer details'];
        }

        $successCount = count($detailData);
        $config['params']['trno'] = $trno;
        $config['params']['customer_accounts'] = $customerAccounts;
        $return = $this->posttrans($config);

        if ($return['status']) {
            $this->logger->sbcwritelog($trno, $config, 'POSTED', $newdocno . ' MR (' . $billtype . ') GENERATED WITH ' . $successCount . ' CUSTOMERS');
            return [
                'status' => true,
                'docno' => $newdocno,
                'msg' => 'New MR Transaction Generated ' . $newdocno . ' (' . $billtype . ') with ' . $successCount . ' customers. Total Amount: ' . number_format($totalAmount, 2)
            ];
        } else {
            $this->coreFunctions->execqry('delete from cntnum where trno=?', 'delete', [$trno]);
            $this->coreFunctions->execqry('delete from lahead where trno=?', 'delete', [$trno]);
            $this->coreFunctions->execqry('delete from ladetail where trno=?', 'delete', [$trno]);
            $this->coreFunctions->execqry('delete from glhead where trno=?', 'delete', [$trno]);
            $this->coreFunctions->execqry('delete from gldetail where trno=?', 'delete', [$trno]);
            return ['status' => false, 'msg' => $return['msg']];
        }
    } // end function

    public function createdistribution($config)
    {
        $trno = $config['params']['trno'];
        $companyid = $config['params']['companyid'];
        $user = $config['params']['user'];
        $datenow = $this->othersClass->getCurrentTimeStamp();
        $acctg = array();

        $customerAccounts = isset($config['params']['customer_accounts']) ? $config['params']['customer_accounts'] : array();

        $head = $this->coreFunctions->opentable("select client, contra, cur, forex, dateid, tax from lahead where trno=?", array($trno));
        if (empty($head)) {
            return false;
        }

        $details = $this->coreFunctions->opentable("select * from ladetail where trno=? order by line", array($trno));
        if (empty($details)) {
            return false;
        }

        $isvatexsales = $this->companysetup->getvatexsales($config['params']);
        $defaultRevAcnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', array('SA1'));

        $vatrate = 12; // fixed VAT rate when isvat = 1
        $tax1 = 1 + ($vatrate / 100);
        $tax2 = $vatrate / 100;

        $lineCounter = 0;
        foreach ($details as $detail) {
            $lineCounter++;

            $tax = 0;
            if ($detail->isvat == 1) {
                if ($isvatexsales) {
                    $tax = number_format(($detail->db * $tax2), 4, '.', '');
                } else {
                    $tax = number_format(($detail->db / $tax1), 4, '.', '');
                    $tax = number_format($detail->db - $tax, 4, '.', '');
                }
            }

            $revAcnoid = $defaultRevAcnoid;
            if (isset($customerAccounts[$lineCounter - 1]) && !empty($customerAccounts[$lineCounter - 1]['rev_acnoid'])) {
                $revAcnoid = $customerAccounts[$lineCounter - 1]['rev_acnoid'];
            }

            // AR entry (Debit) - full billed amount
            $entry = array(
                'acnoid' => $detail->acnoid,
                'client' => $detail->client,
                'db' => $detail->db * $head[0]->forex,
                'cr' => 0,
                'postdate' => $head[0]->dateid,
                'cur' => $head[0]->cur,
                'forex' => $head[0]->forex,
                'fdb' => floatval($head[0]->forex) == 1 ? 0 : $detail->db,
                'fcr' => 0
            );
            $acctg[] = $entry;

            // Revenue entry (Credit) - net of tax if isvat
            $salesAmount = $detail->db - $tax;
            $entry = array(
                'acnoid' => $revAcnoid,
                'client' => $detail->client,
                'db' => 0,
                'cr' => $salesAmount * $head[0]->forex,
                'postdate' => $head[0]->dateid,
                'cur' => $head[0]->cur,
                'forex' => $head[0]->forex,
                'fcr' => floatval($head[0]->forex) == 1 ? 0 : $salesAmount,
                'fdb' => 0
            );
            $acctg[] = $entry;

            // VAT entry - only if isvat = 1 and tax > 0
            if ($tax > 0) {
                $vatAcnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', array('TX2'));
                $entry = array(
                    'acnoid' => $vatAcnoid,
                    'client' => $detail->client,
                    'db' => 0,
                    'cr' => $tax * $head[0]->forex,
                    'postdate' => $head[0]->dateid,
                    'cur' => $head[0]->cur,
                    'forex' => $head[0]->forex,
                    'fcr' => floatval($head[0]->forex) == 1 ? 0 : $tax,
                    'fdb' => 0
                );
                $acctg[] = $entry;
            }
        }

        // Sanitize and insert into ladetail
        if (!empty($acctg)) {
            $dateTables = array('ladetail');
            $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, array(), false, $dateTables);
            $current_timestamp = $this->othersClass->getCurrentTimeStamp();

            $this->coreFunctions->execqry('delete from ladetail where trno=?', 'delete', array($trno));

            foreach ($acctg as $key => $value) {
                foreach ($value as $key2 => $value2) {
                    $acctg[$key][$key2] = $this->othersClass->sanitizekeyfieldFast($key2, $value2, $lookups);
                }
                $acctg[$key]['editdate'] = $current_timestamp;
                $acctg[$key]['editby'] = $user;
                $acctg[$key]['encodeddate'] = $current_timestamp;
                $acctg[$key]['encodedby'] = $user;
                $acctg[$key]['trno'] = $trno;
                $acctg[$key]['db'] = round($acctg[$key]['db'], 2);
                $acctg[$key]['cr'] = round($acctg[$key]['cr'], 2);
                $acctg[$key]['fdb'] = round($acctg[$key]['fdb'], 2);
                $acctg[$key]['fcr'] = round($acctg[$key]['fcr'], 2);
                $acctg[$key]['line'] = $key + 1;
            }

            if ($this->coreFunctions->sbcinsert($this->detail, $acctg) == 1) {
                $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'MR DISTRIBUTION SUCCESS');
                return true;
            } else {
                $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'MR DISTRIBUTION FAILED');
                return false;
            }
        }

        return true;
    } // end function

    public function generateyb($config)
    {
        $companyid = $config['params']['companyid'];
        $dateid = $config['params']['dataparams']['dateid'];
        $date = new DateTime($dateid);
        $monthYear = $date->format('F Y');
        $currentYear = date('Y');  // Get current year

        $doc = 'SJ';
        $center = $config['params']['center'];

        // Get billtypes that exist for ANNUAL billing (sched = 1)
        $billtypes = $this->coreFunctions->opentable(
            "select distinct billtype from billingmaster where sched = 1"
        );

        if (empty($billtypes)) {
            return ['status' => false, 'msg' => 'No annual bill types found in billing setup'];
        }

        $generatedDocs = [];
        $failedDocs = [];
        $msg2 = '';

        // Loop through each billtype
        foreach ($billtypes as $bt) {
            $billtype = $bt->billtype;

            $qry = "select c.clientid, c.client, c.clientname, c.addr, c.tax, c.vattype,
                cb.amt, cb.isvat, bm.billtype, bm.sched,
                ca.acno as ar_account, cr.acno as revenue_account,
                ca.acnoid as ar_acnoid, cr.acnoid as revenue_acnoid,
                cb.bline
                from clbilling as cb
                left join billingmaster as bm on bm.line = cb.bline
                left join client as c on c.clientid = cb.clientid
                left join coa as ca on ca.acnoid = bm.assetid
                left join coa as cr on cr.acnoid = bm.revenueid
                where cb.amt <> 0 and c.iscustomer = 1
                and bm.sched = 1 and bm.billtype = ?
                order by c.clientname";

            $data = $this->coreFunctions->opentable($qry, [$billtype]);

            if (empty($data)) {
                $msg2 .= 'No customers with ' . $billtype . ' annual billing setup found.<br>';
                continue;
            }

            $eligibleLines = [];
            foreach ($data as $row) {
                // Check if customer already has YB for this year
                $isgenerate = $this->isybgenerated($config, $row->clientid, $doc, $billtype);
                if (!$isgenerate) {
                    $eligibleLines[] = $row;
                }
            }

            if (empty($eligibleLines)) {
                $msg2 .= 'All ' . count($data) . ' customer(s) with ' . $billtype . ' already generated this year.<br>';
                continue;
            }

            // Process each customer individually (one document per customer per billtype)
            foreach ($eligibleLines as $customer) {
                $result = $this->generateybdoc($config, $billtype, $customer, $date, $monthYear, $currentYear);

                if ($result['status']) {
                    $generatedDocs[] = $result['docno'] . ' (' . $billtype . ' - ' . $customer->client . ')';
                } else {
                    $failedDocs[] = $billtype . ' - ' . $customer->client;
                    $msg2 .= $result['msg'] . ' for ' . $billtype . ' - ' . $customer->client . '<br>';
                }
            }
        }

        if (!empty($generatedDocs)) {
            $resultMsg = 'YB Documents Generated: ' . implode(', ', $generatedDocs);
            if ($msg2 != '') {
                $resultMsg .= '<br>' . $msg2;
            }
            return ['status' => true, 'msg' => $resultMsg];
        } else {
            if ($msg2 != '') {
                return ['status' => false, 'msg' => 'YB Generation Failed: ' . $msg2];
            }
            return ['status' => false, 'msg' => 'No new customers found for YB generation this year'];
        }
    }

    public function isybgenerated($config, $clientid, $doc, $billtype = '')
    {
        $currentYear = date('Y');

        $document = $this->coreFunctions->datareader(
            "select gd.trno as value
            from gldetail as gd
            left join glhead as gh on gh.trno = gd.trno
            where gd.clientid = ?
            and gh.doc = ? 
            and left(gh.docno,2) = 'YB' 
            and gh.ourref = ?
            and year(gh.dateid) = ?
            limit 1",
            [$clientid, $doc, $billtype, $currentYear]
        );

        if ($document === '' || $document === null) {
            return false;
        } else {
            return true;
        }
    }

    private function generateybdoc($config, $billtype, $customer, $date, $monthYear, $currentYear)
    {
        $companyid = $config['params']['companyid'];
        $doc = 'SJ';
        $sjref = 'SJ';
        $ybref = 'YB';
        $table = 'cntnum';
        $center = $config['params']['center'];
        $docnolength = $this->companysetup->getdocumentlength($config['params']);
        $user = $config['params']['user'];
        $cur = $this->companysetup->getdefaultcurrency($config['params']);
        $datenow = $this->othersClass->getCurrentTimeStamp();
        $forex = 1;
        $defcontra = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['AR1']);
        $defcontra_acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['AR1']);
        $wh = $this->coreFunctions->getfieldvalue("center", "warehouse", "code=?", [$center]);
        $dateTables = ['lahead', 'ladetail'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);
        $newdocno = '';
        $trno = 0;

        // Reserve document number with YB prefix
        $insert = 0;
        $attempts = 0;
        while ($insert == 0) {
            $attempts++;
            if ($attempts > 20) {
                return ['status' => false, 'msg' => 'Unable to reserve a document number after multiple attempts'];
            }
            $getdoc = $this->coreFunctions->getfieldvalue($table, 'doc', 'bref=?', [$ybref]);
            $seq = $this->othersClass->getlastseq($ybref, $config, $table);
            if ($getdoc == '') {
                $seq = $this->othersClass->getlastseq($sjref, $config, $table);
            }
            $ybseq = $ybref . $seq;
            $newdocno = $this->othersClass->PadJ($ybseq, $docnolength);
            $col = ['doc' => $doc, 'docno' => $newdocno, 'seq' => $seq, 'bref' => $ybref, 'center' => $center];
            $insert = $this->coreFunctions->insertGetId($table, $col);
        }

        $qry = "select trno,docno from cntnum where doc = ? and docno = ? and center = ? ";
        $data2 = $this->coreFunctions->opentable($qry, [$doc, $newdocno, $center]);

        if (empty($data2)) {
            return ['status' => false, 'msg' => 'Failed to generate document number'];
        }

        $trno = $data2[0]->trno;
        $docno = $data2[0]->docno;

        // Get AR and Revenue accounts for this customer
        $arAcnoid = $customer->ar_acnoid ?: $defcontra_acnoid;
        $revAcnoid = $customer->revenue_acnoid ?: $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['SA1']);

        $customerAccounts = [
            [
                'client' => $customer->client,
                'ar_acnoid' => $arAcnoid,
                'rev_acnoid' => $revAcnoid,
                'amt' => $customer->amt,
                'isvat' => $customer->isvat,
                'bline' => $customer->bline
            ]
        ];

        $remlabel = "Annual Service Fee for " . $billtype . " for the year " . $currentYear;

        // Create header with customer info
        $datahead = [
            'trno' => $trno,
            'doc' => $doc,
            'docno' => $docno,
            'client' => $customer->client,
            'clientname' => $customer->clientname,
            'address' => $customer->addr,
            'ourref' => $billtype,
            'dateid' => $date->format('Y-m-d'),
            'due' => $date->format('Y-m-d'),
            'cur' => $cur,
            'contra' => $defcontra,
            'wh' => $wh,
            'forex' => $forex,
            'deldate' => $date->format('Y-m-d'),
            'tax' => $customer->tax,
            'vattype' => $customer->vattype,
            'rem' => $remlabel
        ];

        foreach ($datahead as $key2 => $val) {
            if (!in_array($key2, $this->except)) {
                $datahead[$key2] = $this->othersClass->sanitizekeyfieldFast($key2, $datahead[$key2], $lookups);
            }
        }
        $datahead['createdate'] = $datenow;
        $datahead['createby'] = $user;
        $insert = $this->coreFunctions->sbcinsert($this->head, $datahead);

        if (!$insert) {
            $this->coreFunctions->execqry('delete from cntnum where trno=?', 'delete', [$trno]);
            return ['status' => false, 'msg' => 'Failed to create header'];
        }

        // Insert detail for this customer
        $detailRow = [
            'trno' => $trno,
            'line' => 1,
            'client' => $customer->client,
            'rem' => $remlabel,
            'db' => $customer->amt,
            'cr' => 0,
            'fdb' => $customer->amt,
            'fcr' => 0,
            'forex' => $forex,
            'agent' => '',
            'postdate' => $date->format('Y-m-d'),
            'ref' => '',
            'checkno' => '',
            'duedate' => $date->format('Y-m-d'),
            'refx' => 0,
            'linex' => 0,
            'clearday' => null,
            'encodeddate' => $datenow,
            'encodedby' => $user,
            'isvat' => $customer->isvat,
            'damt' => $customer->amt,
            'cur' => $cur,
            'acnoid' => $arAcnoid,
            'void' => 0,
            'deptid' => 0,
            'branch' => 0,
            'agentid' => 0
        ];

        foreach ($detailRow as $key2 => $val) {
            $detailRow[$key2] = $this->othersClass->sanitizekeyfieldFast($key2, $detailRow[$key2], $lookups);
        }

        $insertDetails = $this->coreFunctions->sbcinsert($this->detail, [$detailRow]);

        if (!$insertDetails) {
            $this->coreFunctions->execqry('delete from cntnum where trno=?', 'delete', [$trno]);
            $this->coreFunctions->execqry('delete from lahead where trno=?', 'delete', [$trno]);
            return ['status' => false, 'msg' => 'Failed to insert customer details'];
        }

        $config['params']['trno'] = $trno;
        $config['params']['customer_accounts'] = $customerAccounts;
        $return = $this->posttrans($config);

        if ($return['status']) {
            $this->logger->sbcwritelog($trno, $config, 'POSTED', $newdocno . ' YB (' . $billtype . ') GENERATED FOR ' . $customer->client);
            return [
                'status' => true,
                'docno' => $newdocno,
                'msg' => 'New YB Transaction Generated ' . $newdocno . ' (' . $billtype . ') for ' . $customer->client . '. Amount: ' . number_format($customer->amt, 2)
            ];
        } else {
            $this->coreFunctions->execqry('delete from cntnum where trno=?', 'delete', [$trno]);
            $this->coreFunctions->execqry('delete from lahead where trno=?', 'delete', [$trno]);
            $this->coreFunctions->execqry('delete from ladetail where trno=?', 'delete', [$trno]);
            $this->coreFunctions->execqry('delete from glhead where trno=?', 'delete', [$trno]);
            $this->coreFunctions->execqry('delete from gldetail where trno=?', 'delete', [$trno]);
            return ['status' => false, 'msg' => $return['msg']];
        }
    }

    public function posttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];
        $datenow = $this->othersClass->getCurrentTimeStamp();
        $action = isset($config['params']['action2']) ? $config['params']['action2'] : '';

        // Check if already posted
        $isPosted = $this->coreFunctions->datareader("select statid as value from cntnum where trno=? and statid=12", [$trno]);
        if ($isPosted) {
            return ['trno' => $trno, 'status' => false, 'msg' => 'Already posted.'];
        }

        // Create distribution from ladetail
        if (!$this->createdistribution($config)) {
            return ['trno' => $trno, 'status' => false, 'msg' => 'Failed to create accounting entries.'];
        }

        // Move lahead to glhead
        $lahead = $this->coreFunctions->opentable("select * from lahead where trno=?", [$trno]);
        if (!empty($lahead)) {
            // For YB documents, get clientid from the client
            $clientid = 0;
            if ($action == 'generateyb') {
                $client = $lahead[0]->client;
                if (!empty($client)) {
                    $clientid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$client]);
                }
            }

            $glheadData = [
                'trno' => $lahead[0]->trno,
                'doc' => $lahead[0]->doc,
                'docno' => $lahead[0]->docno,
                'clientid' => $clientid,  // Only clientid, no client column
                'clientname' => $lahead[0]->clientname,
                'address' => $lahead[0]->address,
                'dateid' => $lahead[0]->dateid,
                'due' => $lahead[0]->due,
                'cur' => $lahead[0]->cur,
                'contra' => $lahead[0]->contra,
                'whid' => $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$lahead[0]->wh]),
                'forex' => $lahead[0]->forex,
                'deldate' => $lahead[0]->deldate,
                'tax' => $lahead[0]->tax,
                'vattype' => $lahead[0]->vattype,
                'ourref' => $lahead[0]->ourref,
                'rem' => $lahead[0]->rem,
                'createdate' => $lahead[0]->createdate,
                'createby' => $lahead[0]->createby
            ];
            $this->coreFunctions->sbcinsert('glhead', $glheadData);
        }

        // Move ladetail to gldetail
        $ladetail = $this->coreFunctions->opentable("select * from ladetail where trno=?", [$trno]);
        if (!empty($ladetail)) {
            $glDetailData = [];
            foreach ($ladetail as $detail) {
                $clientid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$detail->client]);
                $glDetailRow = [
                    'trno' => $detail->trno,
                    'line' => $detail->line,
                    'clientid' => $clientid,  // Only clientid, no client column
                    'rem' => $detail->rem,
                    'db' => $detail->db,
                    'cr' => $detail->cr,
                    'fdb' => $detail->fdb,
                    'fcr' => $detail->fcr,
                    'forex' => $detail->forex,
                    'postdate' => $datenow,
                    'ref' => $detail->ref,
                    'checkno' => $detail->checkno,
                    'duedate' => $detail->duedate,
                    'refx' => $detail->refx,
                    'linex' => $detail->linex,
                    'clearday' => $detail->clearday,
                    'encodeddate' => $detail->encodeddate,
                    'encodedby' => $detail->encodedby,
                    'isvat' => $detail->isvat,
                    'damt' => $detail->damt,
                    'cur' => $detail->cur,
                    'acnoid' => $detail->acnoid,
                    'void' => $detail->void,
                    'deptid' => $detail->deptid,
                    'branch' => $detail->branch,
                    'agentid' => $detail->agentid
                ];
                $glDetailData[] = $glDetailRow;
            }
            $this->coreFunctions->sbcinsert('gldetail', $glDetailData);
        }

        // Update cntnum status
        $this->coreFunctions->sbcupdate('cntnum', ['statid' => 12, 'postdate' => $datenow, 'postedby' => $user], ['trno' => $trno]);

        // Delete from lahead and ladetail
        $this->coreFunctions->execqry('delete from lahead where trno=?', 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ladetail where trno=?', 'delete', [$trno]);

        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
    }
} //end class
