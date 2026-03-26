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

class entrygeneratepr
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'STOCK REQUEST DETAILS';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $head = 'htrhead';
    private $stock = 'htrstock';
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['itemname', 'rrqty'];
    public $showclosebtn = true;


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

    public function createTab($config)
    {
        $tab = [$this->gridname => ['gridcolumns' => ['action', 'itemname', 'rrqty', 'prqty', 'qa', 'uom',  'rem', 'wh']]];

        $stockbuttons = ['showbalance'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][1]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][2]['label'] = 'Approve Qty';
        $obj[0][$this->gridname]['columns'][2]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][5]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][5]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][6]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][7]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][7]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][0]['btns']['showbalance']['name'] = 'lookup';
        $obj[0][$this->gridname]['columns'][0]['btns']['showbalance']['action'] = 'showbalance';
        $obj[0][$this->gridname]['columns'][0]['btns']['showbalance']['lookupclass'] = 'showbalance';

        return $obj;
    }


    public function createtabbutton($config)
    {
        $tbuttons = ['saveall', 'generatepr'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function getdata($config)
    {
        $trno = $config['params']['tableid'];
        $msg = '';
        $status = true;

        if ($this->isprgenerated($config)) {
            $msg = 'Purchase requisiontion already generated.';
            $status = false;
        } else {
            $data = $config['params']['data'];
            foreach ($data as $key => $value) {
                $qty = $value['prqty'];
                $var = [
                    'prqty' => $qty
                ];
                $this->coreFunctions->sbcupdate($this->stock, $var, ['trno' => $value['trno'], 'line' => $value['line']]);
            }
            $msg = 'Saved changes...';
        }

        $data = app('App\Http\Classes\modules\issuance\trapproval')->openstock($trno, $config);
        return ['status' => $status, 'msg' => $msg, 'data' => $data];
    } //end function

    public function approveall($config)
    {
        $status = true;
        $msg = '';
        $trno = $config['params']['tableid'];

        if ($this->isprgenerated($config)) {
            $msg = 'Purchase requisiontion already generated.';
            $status = false;
        } else {
            $data = app('App\Http\Classes\modules\issuance\trapproval')->openstock($trno, $config);

            $zeroprqty = true;

            foreach ($data as $key => $value) {
                // if ($value->prqty == 0) {
                //     $msg = 'Unable to generate Purchase requisition, all items are zero quantity';
                //     $status = false;
                //     goto returnhere;
                // }

                if ($value->prqty != 0) {
                    $zeroprqty = false;
                }

                if ($value->prqty > $value->qa) {
                    $msg = 'Unable to generate Purchase requisition, PR quantity must not be greater than pending quantity';
                    $status = false;
                    goto returnhere;
                }
            }

            if ($zeroprqty) {
                $msg = 'Unable to generate Purchase requisition, all items are zero quantity';
                $status = false;
                goto returnhere;
            }

            $result = $this->generatepr($config);
            if ($result['status']) {

                $date = date("Y-m-d H:i:s");
                $var = [
                    'prdate' => $date,
                    'prby' => $config['params']['user'],
                    'ourref' => $result['msg']
                ];
                $this->coreFunctions->sbcupdate($this->head, $var, ['trno' => $trno]);

                $msg = $result['msg'] . ' generated.';
            } else {
                $msg = $result['msg'];
                $status = false;
            }
        }

        returnhere:
        $data = app('App\Http\Classes\modules\issuance\trapproval')->openstock($trno, $config);
        return ['status' => $status, 'msg' => $msg, 'data' => $data, 'reloaddata' => true];
    } //end function

    public function loaddata($config)
    {
        $trno = $config['params']['tableid'];
        $data = app('App\Http\Classes\modules\issuance\trapproval')->openstock($trno, $config);
        return $data;
    }

    public function isprgenerated($config)
    {
        $trno = $config['params']['tableid'];
        $table = $this->head;
        $document = $this->coreFunctions->datareader("select prdate as value from $table where trno = ? limit 1", [$trno]);
        if ($document === '' || $document === null) {
            return false;
        } else {
            return true;
        }
    } //end fn

    private function generatepr($config)
    {
        $status = false;
        $msg = '';

        $trno = 0;

        try {
            $doc = 'PR';
            $pref = 'PR';
            $table = 'transnum';
            $center = $config['params']['center'];

            $docnolength =  $this->companysetup->getdocumentlength($config['params']);
            $insertcntnum = 0;

            while ($insertcntnum == 0) {
                $seq = $this->othersClass->getlastseq($pref, $config, $table);
                if ($seq == 0 || empty($pref)) {
                    if (empty($pref)) {
                        $pref = strtoupper($doc);
                    }
                    $seq = $this->othersClass->getlastseq($pref, $config, $table);
                }

                $poseq = $pref . $seq;
                $newdocno = $this->othersClass->PadJ($poseq, $docnolength);

                $col = ['doc' => $doc, 'docno' => $newdocno, 'seq' => $seq, 'bref' => $pref, 'center' => $center];
                $insertcntnum = $this->coreFunctions->insertGetId($table, $col);
            }

            $qry = "select trno,docno from " . $table . " where doc = ? and docno = ? and center = ?";
            $trno_ =  $this->coreFunctions->opentable($qry, [$doc, $newdocno, $center]);

            if ($trno_) {
                $trno = $trno_[0]->trno;
                $docno = $trno_[0]->docno;
                $user = $config['params']['user'];

                $qry = "select trno,docno,client,clientname,wh from htrhead where trno = ? ";
                $trhead = $this->coreFunctions->opentable($qry, [$config['params']['tableid']]);

                if ($trhead) {
                    $client = $trhead[0]->client;
                    $clientname = $trhead[0]->clientname;
                    $wh = $trhead[0]->wh;
                    $dateid = date("Y-m-d");

                    $varhead = [
                        'trno' => $trno,
                        'doc' => $doc,
                        'docno' => $docno,
                        'client' => $client,
                        'clientname' => $clientname,
                        'cur' => 'P',
                        'forex' => '1',
                        'dateid' => $dateid,
                        'createdate' => date("Y-m-d H:i:s"),
                        'createby' => $user,
                        'wh' => $wh,
                        'rem' => 'Auto generated from stock request'
                    ];
                    $inserthead = $this->coreFunctions->sbcinsert('prhead', $varhead);
                    if ($inserthead) {

                        $data = $config['params']['data'];
                        $line = $this->othersClass->getstockline('prstock', $trno) + 1;

                        foreach ($data as $key => $value) {

                            if ($value['prqty'] == 0) continue;

                            $qa = $this->othersClass->sanitizekeyfield('qa', $value['qa']);
                            if ($qa > 0) {

                                $itemid = $value['itemid'];
                                $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
                                $item = $this->coreFunctions->opentable($qry, [$value['uom'], $itemid]);
                                $factor = 1;
                                if (!empty($item)) {
                                    $item[0]->factor = $this->othersClass->val($item[0]->factor);
                                    if ($item[0]->factor !== 0) $factor = $item[0]->factor;
                                }

                                $cost = floatval($this->othersClass->getlatestcost($itemid, $dateid, $config, $value['wh']));
                                $computedata = $this->othersClass->computestock($cost, '', $qa, $factor);

                                $varstock = [
                                    'trno' => $trno,
                                    'line' => $line,
                                    'itemid' => $itemid,
                                    'uom' => $value['uom'],
                                    'whid' => $this->coreFunctions->getfieldvalue("client", "clientid", "client = '" . $value['wh'] . "'"),
                                    'rrqty' => $qa,
                                    'rrcost' => $cost,
                                    'qty' => $computedata['qty'],
                                    'cost' => $computedata['amt'],
                                    'ext' => $computedata['ext'],
                                    'ref' => $trhead[0]->docno,
                                    'refx' => $value['trno'],
                                    'linex' => $value['line']
                                ];
                                $inserstock = $this->coreFunctions->sbcinsert('prstock', $varstock);
                                if ($inserstock) {

                                    if ($this->setserveditems($value['trno'], $value['line'])) {
                                    } else {
                                        $data2 = ['rrqty' => 0, 'qty' => 0, 'ext' => 0];
                                        $this->coreFunctions->sbcupdate('prstock', $data2, ['trno' => $trno, 'line' => $line]);
                                        $this->setserveditems($value['trno'], $value['line']);
                                        $msg = 'Failed to served quantity applied';
                                        goto exithere;
                                    }

                                    $line++;
                                } else {
                                    $msg = 'Failed to insert stock details';
                                    goto exithere;
                                }
                            }
                        } //end for each

                        $this->logger->sbcwritelog2($trno, $user, 'CREATE', 'Auto generated ' . $docno . ' from stock request', $table . '_log');

                        $postresult = $this->postpr($trno, $user);
                        if ($postresult['status']) {
                            $status = true;
                            $msg = $docno;
                        } else {
                            $msg = $postresult['msg'];
                            goto exithere;
                        }
                    } else {
                        $msg = 'Failed to insert header details';
                        goto exithere;
                    }
                } else {
                    $msg = 'Failed to retrieve stock request details Please advice your provider';
                    goto exithere;
                }
            } else {
                $msg = 'Failed to retrieve trno. Please advice your provider';
            }
        } catch (\Exception $e) {
            $msg = 'Failed to generate purchase requisition -> ' . $e;
        }

        exithere:
        if (!$status) {
            $qry = 'delete from ' . $table . ' where trno=?';
            $this->coreFunctions->execqry($qry, 'delete', [$trno]);
            $qry = 'delete from prhead where trno=?';
            $this->coreFunctions->execqry($qry, 'delete', [$trno]);
            $qry = 'delete from prstock where trno=?';
            $this->coreFunctions->execqry($qry, 'delete', [$trno]);
        }

        return ['status' => $status, 'msg' => $msg];
    }



    public function setserveditems($refx, $linex)
    {
        $status = true;
        try {
            $qry1 = "select stock.qty as qty from prhead as head left join prstock as 
            stock on stock.trno=head.trno where head.doc='PR' and stock.refx=" . $refx . " and stock.linex=" . $linex;

            $qry1 .= " union all select stock.qty as qty from hprhead as head left join hprstock as stock on stock.trno=
          head.trno where head.doc='PR' and stock.refx=" . $refx . " and stock.linex=" . $linex;

            $qry2 = "select ifnull(sum(qty),0) as value from (" . $qry1 . ") as t";

            $qty = $this->coreFunctions->datareader($qry2);

            if ($qty === '') {
                $qty = 0;
            }

            $this->coreFunctions->execqry("update htrstock set prqa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
        } catch (\Exception $e) {
            $status = false;
            throw new \Exception('Exception message => ' . $e);
        }
        return $status;
    }


    private function postpr($trno, $user)
    {
        $status = false;
        $msg = '';

        try {

            $tablenum = 'transnum';

            $docno = $this->coreFunctions->datareader('select docno as value from ' . $tablenum  . ' where trno=?', [$trno]);

            if ($this->othersClass->isposted2($trno, $tablenum)) {
                $msg = 'Posting failed. Transaction has already been posted.';
                goto exithere;
            }
            //for glhead
            $qry = "insert into hprhead(trno,doc,docno,client,clientname,address,shipto,dateid,terms,rem,forex,yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,agent,wh,due,cur)
            SELECT head.trno,head.doc, head.docno,head.client, head.clientname, head.address,head.shipto,head.dateid as dateid, head.terms, head.rem, head.forex,head.yourref, head.ourref,
            head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,head.agent,head.wh,head.due,head.cur 
            FROM prhead as head left join cntnum on cntnum.trno=head.trno where head.trno=? limit 1";
            $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
            if ($posthead) {
                // for glstock
                $qry = "insert into hprstock (trno,line,itemid,uom,whid,loc,ref,disc,cost,qty,void,rrcost,rrqty,ext,encodeddate,qa,encodedby,editdate,editby,refx,linex) 
                SELECT trno, line, itemid, uom,whid,loc,ref,disc,cost, qty,void,rrcost, rrqty, ext,encodeddate,qa, encodedby,editdate,editby,refx,linex FROM prstock where trno =?";

                if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
                    //update transnum
                    $date = $this->othersClass->getCurrentTimeStamp();
                    $data = ['postdate' => $date, 'postedby' => $user];
                    $this->coreFunctions->sbcupdate($tablenum, $data, ['trno' => $trno]);
                    $this->coreFunctions->execqry("delete from prstock where trno=?", "delete", [$trno]);
                    $this->coreFunctions->execqry("delete from prhead where trno=?", "delete", [$trno]);
                    $this->logger->sbcwritelog2($trno, $user, 'POSTED', $docno, $tablenum . '_log');
                    $status = true;
                } else {
                    $this->coreFunctions->execqry("delete from hprhead where trno=?", "delete", [$trno]);
                    $msg = 'Error on Posting stock';
                    goto exithere;
                }
            } else {
                $msg = 'Error on Posting Head';
            }
        } catch (\Exception $e) {
            throw new \Exception('Exception message => ' . $e);
        }

        exithere:
        return ['status' => $status, 'msg' => $msg];
    } //end function    
} //end class
