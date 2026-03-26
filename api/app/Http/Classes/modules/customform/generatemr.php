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
        $fields = [];
        $col3 = $this->fieldClass->create($fields);
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
        $data =  $this->coreFunctions->opentable($qry);
        switch ($action) {
            case 'generatemr':
                return $this->generatemr($config);

                break;
        }
    }
    public function data()
    {
        return [];
    }
    public function generatemr($config)
    {
        $dateid = $config['params']['dataparams']['dateid'];
        $date = new DateTime($dateid);
        $qry = "select clientid,client,clientname,addr,terms,charge1 from client where charge1 <> 0 and iscustomer = 1 order by clientname";
        $data = $this->coreFunctions->opentable($qry);
        $msg = '';
        $msg2 = '';
        $trno = 0;
        $status = true;
        if (!empty($data)) {
            foreach ($data as $key => $value) {

                $doc = 'SJ';
                $sjref = 'SJ';
                $mrref = 'MR';
                $table = 'cntnum';
                $center = $config['params']['center'];
                $isgenerate = $this->isprgenerated($config, $data[$key]->clientid, $date->format('Y-m'), $doc);
                $insert = 0;
                if (!$isgenerate) {
                    while ($insert == 0) {
                        $docnolength =  $this->companysetup->getdocumentlength($config['params']);
                        //get seq ng sj then check if my mr doc na then use mr else get sj seq
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
                } else {
                    continue;
                }
                $qry = "select trno,docno from cntnum where doc = ? and docno = ? and center = ? ";
                $data2 =  $this->coreFunctions->opentable($qry, [$doc, $newdocno, $center]);
                if (!empty($data2)) {
                    $trno =  $data2[0]->trno;
                    $docno = $data2[0]->docno;
                    $this->coreFunctions->logconsole($trno . '---' . $docno);
                    $user = $config['params']['user'];
                    $center = $config['params']['center'];
                    //  insert headdata 
                    $defcontra = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['AR1']);
                    $contra = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$defcontra]);

                    $wh = $this->coreFunctions->getfieldvalue("center", "warehouse", "code=?", [$center]);
                    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
                    $cur = $this->companysetup->getdefaultcurrency($config['params']);
                    //get all default for creating new trans
                    $datenow = $this->othersClass->getCurrentTimeStamp();
                    $forex = 1;
                    $datahead = [
                        'trno' => $trno,
                        'doc' => $doc,
                        'docno' => $docno,
                        'client' => $data[$key]->client,
                        'clientname' => $data[$key]->clientname,
                        'address' => $data[$key]->addr,
                        'dateid' => $date->format('Y-m-d'),
                        'due' => date('Y-m-d'),
                        'cur' => $cur,
                        'contra' => $defcontra,
                        'wh' => $wh,
                        'forex' => $forex,
                        'deldate' => date('Y-m-d'),
                        'tax' => 0,
                        'vattype' => 'NON-VATABLE',
                        'rem' => "Service Fee for the month of " . date("F", strtotime($date->format('Y-m-d'))) . " " . date('Y', strtotime($date->format('Y-m-d')))
                    ];


                    foreach ($datahead as $key2 => $val) {
                        if (!in_array($key, $this->except)) {
                            $datahead[$key2] = $this->othersClass->sanitizekeyfield($key2, $datahead[$key2]);
                        } //end if
                    }
                    $datahead['createdate'] = $datenow;
                    $datahead['createby'] = $config['params']['user'];
                    $insert = $this->coreFunctions->sbcinsert($this->head, $datahead);
                    // stock insert data from item
                    // itemid = 2004 testing change nalang
                    if ($insert) {
                        $qry = "select itemid,itemname,uom from item where itemid = 18";
                        $isnoninv =  $this->coreFunctions->opentable($qry);
                        if (!empty($isnoninv)) {
                            $disc = '';
                            $qty = 1;
                            $kgs = 0;
                            $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
                            $item = $this->coreFunctions->opentable($qry, [$isnoninv[0]->uom, $isnoninv[0]->itemid]);
                            $factor = 1;
                            if (!empty($item)) {
                                $item[0]->factor = $this->othersClass->val($item[0]->factor);
                                if ($item[0]->factor !== 0) $factor = $item[0]->factor;
                            }
                            $amt = $data[$key]->charge1;
                            $amt = $this->othersClass->sanitizekeyfield('amt', $amt);
                            $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));
                            $qty = $this->othersClass->sanitizekeyfield('qty', $qty);
                            $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, $cur, $kgs);
                            $hamt = $computedata['amt'] * $forex;
                            $hamt = $this->othersClass->sanitizekeyfield('amt', $hamt);

                            $ext = number_format($qty * $amt, 2);
                            $hamt = number_format($hamt, 2);

                            $qry = "select line as value from $this->stock where trno= ? order by line desc limit 1";
                            $line = $this->coreFunctions->datareader($qry, [$trno]);
                            if ($line == '') {
                                $line = 0;
                            }
                            $line = $line + 1;
                            $datastock = [
                                'trno' => $trno,
                                'line' => $line,
                                'refx' => 0,
                                'linex' => 0,
                                'itemid' => $isnoninv[0]->itemid,
                                'uom' => $isnoninv[0]->uom,
                                'isamt' => $hamt,
                                'amt' => $amt,
                                'isqty' => $qty,
                                'iss' => $computedata['qty'],
                                'ext' => $ext,
                                'cost' => 0,
                                'kgs' => $kgs,
                                'disc' => $disc,
                                'whid' => $whid,
                            ];
                            foreach ($datastock as $key2 => $val) {
                                $datastock[$key2] = $this->othersClass->sanitizekeyfield($key2, $datastock[$key2]);
                            }
                            $datastock['encodeddate'] = $datenow;
                            $datastock['encodedby'] = $user;
                            $insertstock = $this->coreFunctions->sbcinsert($this->stock, $datastock);

                            if ($insertstock) {
                                $path = 'App\Http\Classes\modules\sales\sj';
                                $config['params']['clientid'] = $trno;
                                $config['params']['trno'] = $trno;
                                $return = app($path)->posttrans($config);
                                if ($return['status']) {
                                    $msg .= 'New Transaction Generated ' . $newdocno;
                                } else {
                                    $msg2 .= $return['msg'];
                                    goto delete;
                                }
                            } else {
                                $msg2 .= 'Failed to insert stock ' . $data[$key]->client;
                                goto delete;
                            }
                        }
                    } else {
                        delete:
                        $msg2 .= 'Failed to insert header ' . $data[$key]->client;
                        $qry = 'delete from cntnum where trno=?';
                        $this->coreFunctions->execqry($qry, 'delete', [$trno]);
                        $qry = 'delete from lastock where trno=?';
                        $this->coreFunctions->execqry($qry, 'delete', [$trno]);
                        $qry = 'delete from lahead where trno=?';
                        $this->coreFunctions->execqry($qry, 'delete', [$trno]);
                    }
                }
            }
            if ($status) {
                if ($msg2 != '') {
                    return ['status' => false, 'msg' => $msg . '</br>' . $msg2];
                } else {
                    if ($msg != '') {
                        return ['status' => $status, 'msg' => $msg . ' ' . $msg2];
                    } else {
                        return ['status' => $status, 'msg' => 'No customer found with retainers fee this month'];
                    }
                }
            }
        } else {
            return ['status' => false, 'msg' => 'No Customer Found with Retainer Fee'];
        }
    }

    public function isprgenerated($config, $clientid, $dateid, $doc)
    {
        $document = $this->coreFunctions->datareader(
            "select dateid as value from glhead where clientid = ? and date_format(dateid, '%Y-%m') = ? and doc = ? and left(docno,2)='MR' limit 1",
            [$clientid, $dateid, $doc]
        );
        if ($document === '' || $document === null) {
            return false;
        } else {
            return true;
        }
    }
} //end class
