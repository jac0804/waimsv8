<?php

namespace App\Http\Classes\modules\ati;

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
use Symfony\Component\VarDumper\VarDumper;

class entrycanvassapproval
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'CANVASS APPROVAL SUMMARY';
    public $gridname = 'inventory';
    private $companysetup;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
    public $tablenum = 'transnum';
    public $head = 'cdhead';
    public $hhead = 'hcdhead';
    public $stock = 'cdstock';
    public $hstock = 'hcdstock';
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';
    public $style = 'width:100%;';
    private $coreFunctions;
    private $othersClass;
    private $fields = ['rrqty'];
    public $showclosebtn = false;
    public $showfilteroption = true;
    public $showfilter = true;
    public $issearchshow = false;

    // public $showcreatebtn = true;
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
            'load' => 4629,
            'view' => 4630

        );
        return $attrib;
    }

    public function createTab($config)
    {


        $columns = ['action', 'ctrlno', 'carem', 'rem', 'dateid', 'docno', 'itemname', 'specs', 'rrcost', 'rrqty2', 'rrqty', 'uom', 'disc', 'ext', 'amt1', 'amt2', 'isprefer', 'isadv', 'requestorname', 'purpose', 'department'];
        foreach ($columns as $key => $value) {
            $$value = $key;
        }
        $tab = [
            $this->gridname => [
                'gridcolumns' => $columns
            ]
        ];

        $stockbuttons = ['save', 'approvesummary', 'disapprovesummary'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['style'] = 'max-height:600px; height:600px; overflow:auto;';

        $obj[0][$this->gridname]['columns'][$rem]['label'] = 'Notes (Canvasser)';
        $obj[0][$this->gridname]['columns'][$rem]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";

        $obj[0][$this->gridname]['columns'][$carem]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";

        $obj[0][$this->gridname]['columns'][$dateid]['style'] = "width:90px;whiteSpace: normal;min-width:90px;";
        $obj[0][$this->gridname]['columns'][$dateid]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$docno]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
        $obj[0][$this->gridname]['columns'][$docno]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$rrcost]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$rrcost]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$rrqty]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
        $obj[0][$this->gridname]['columns'][$rrqty2]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$disc]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
        $obj[0][$this->gridname]['columns'][$disc]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$ext]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";

        $obj[0][$this->gridname]['columns'][$amt1]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$amt2]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";

        $obj[0][$this->gridname]['columns'][$ext]['type'] = "input";

        $obj[0][$this->gridname]['columns'][$amt1]['label'] = "Freight Fees";
        $obj[0][$this->gridname]['columns'][$amt2]['label'] = "Installation Fees";

        $obj[0][$this->gridname]['columns'][$dateid]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$docno]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$ext]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$rem]['type'] = "label";


        $obj[0][$this->gridname]['columns'][$rrcost]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$itemname]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$specs]['type'] = "label";

        $obj[0][$this->gridname]['columns'][$amt1]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$amt2]['type'] = "coldel";

        $obj[0][$this->gridname]['columns'][$itemname]['label'] = "Item Name";

        $obj[0][$this->gridname]['columns'][$itemname]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";

        $obj[0][$this->gridname]['columns'][$rrqty2]['label'] = "PR Qty";
        $obj[0][$this->gridname]['columns'][$rrqty2]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$rrqty2]['align'] = "left";
        $obj[0][$this->gridname]['columns'][$rrqty]['readonly'] = false;

        $obj[0][$this->gridname]['columns'][$isprefer]['checkfield'] = 'isprefer';
        $obj[0][$this->gridname]['columns'][$isadv]['checkfield'] = 'isadv';
        $obj[0][$this->gridname]['columns'][$uom]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$uom]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$purpose]['style'] = "width:280px;whiteSpace: normal;min-width:280px;";
        $obj[0][$this->gridname]['columns'][$purpose]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$department]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";


        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;

        return $obj;
    }


    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    private function selectqry($config)
    {

        $qry = "  
    head.trno,stock.line,
    head.docno,head.clientname,left(head.dateid,10) as dateid,
    stock.status,
    item.partno,
    item.barcode,
    item.itemname,
    item.itemid,
    stock.uom,
    si.uom2,
    si.uom3,
    stock.reqtrno,
    stock.reqline,
    stock.refx,
    stock.linex,
    stock.cost,
    stock.qty as qty,
    FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', 2) . ") as rrcost,
    FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as rrqty,
    FORMAT(stock.ext,2) as ext,
    FORMAT(stock.rrqty2," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as rrqty2,
    stock.disc,
    stock.void,
    stock.ismanual,
    stock.waivedqty,
    case when stock.isprefer=0 then 'false' else 'true' end as isprefer,
    round((stock.qty-stock.qa)/ case when ifnull(uom2.factor,0)<>0 then uom2.factor when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
    CONCAT(CAST(FORMAT(stock.rqcd," . $this->companysetup->getdecimal('qty', $config['params']) . ") as CHAR),' ',stock.uom) as rqcd,
    FORMAT((stock.rrqty2 * case when ifnull(uom3.factor,0)=0 then 1 else uom3.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as basepending,
    stock.ref,
    warehouse.client as wh,
    ifnull(cust.clientname,'') as clientname,
    case 
      when stock.status = 0 then 'Pending'
      when stock.status = 1 then 'Approved'
      when stock.status = 2 then 'Rejected'
    end as canvasstatus,
    stock.rem,ifnull(uom.factor,1) as factor,
    FORMAT(si.amt1," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt1,
    FORMAT(si.amt2," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt2,
    '' as bgcolor,
    ifnull(info.itemdesc,'') as itemdesc, ifnull(info.itemdesc2,'') as itemdesc2, ifnull(info.unit,'') as unit, ifnull(info.specs,'') as specs, ifnull(info.specs2,'') as specs2, ifnull(info.purpose,'') as purpose, date(info.dateneeded) as dateneed,
    ifnull(info.requestorname,'') as requestorname, ifnull(info.rem,'') as rem1, ifnull(sa.sano,'') as sanodesc, si.sono, ifnull(dept.clientname,'') as department,(case when headinfo.isadv <> 0 then 'true' else 'false' end) as isadv,
    ifnull(d.duration,'') as duration,ifnull(cat.category,'') as category,info.ctrlno,si.carem as carem,'' as bgcolor";

        return $qry;
    }
    public function approvedreq($config)
    {
        $row  = $config['params']['row'];
        $msg = 'Successfully updated.';
        $rows = [];
        $status = true;
        $blnApproved = false;
        $deleterow = false;

        foreach ($row as $key2 => $value) {
            $rows[$key2] = $this->othersClass->sanitizekeyfield($key2, $value);
        }
        $canvastats = $this->coreFunctions->getfieldvalue("hcdstock", "status", 'trno = ? and line = ?', [$rows['trno'], $rows['line']]);
        if ($rows['status'] == 0) {

            if ($canvastats != 0) {
                goto ExitHere;
            }
            if ($rows['waivedqty'] == 0) {

                $basetotal = $rows['rrqty2'] * $rows['factor'];

                if ($basetotal == 0) {
                    if ($rows['rrqty'] > $rows['rrqty2']) {
                        $msg = 'Approved quantity must not be greater than request quantity of ' . $rows['rrqty2'];
                        $status = false;
                        goto ExitHere;
                        break;
                    }
                } else {
                    if ($rows['qty'] > $basetotal) {
                        $msg = 'Approved quantity must not be greater than request base quantity base of ' . number_format($basetotal, 2);
                        $status = false;
                        goto ExitHere;
                        break;
                    }
                }

                $approvedqty = $this->coreFunctions->datareader("select ifnull(sum(s.qty),0) as value from hcdstock as s where s.approveddate is not null and s.status=1 and s.void=0 and s.waivedqty=0 and s.reqtrno=? and s.reqline=?", [$rows['reqtrno'], $rows['reqline']], '', true);
                if ($approvedqty != 0) {
                    $basetotal = $rows['rrqty2'] * $rows['factor'];

                    if ($basetotal == 0) {
                        if (($approvedqty + $rows['rrqty']) > $rows['rrqty2']) {
                            return ['status' => false, 'msg' => 'Request quantity of ' . number_format($rows['rrqty2'], 2) . ' for item has already been approved. You are not allow to post another canvass sheet'];
                        }
                    } else {

                        if (($approvedqty + $rows['qty']) > $basetotal) {
                            return ['status' => false, 'msg' => 'Request quantity of ' . number_format($rows['rrqty2'], 2) . ' for item ' . $rows['itemdesc'] . ' has already been approved. You are not allow to approve another canvass sheet. 
                                    Base quantity approved: ' . number_format($approvedqty, 2) . ' ' . $rows['uom'] . ' For approval canvass based quantity: ' . number_format($rows['qty'], 2)];
                        }
                    }
                }
            }


            $current_timestamp = $this->othersClass->getCurrentTimeStamp();
            $qry = "update hcdstock set status=?, approveddate=?,approvedby=?,rem=?  where trno=? and line=?";

            $rows['status'] = 1;
            $this->coreFunctions->execqry($qry, 'update', [$rows['status'], $current_timestamp, $config['params']['user'], $rows['rem'], $rows['trno'], $rows['line']]);
            $qry = "select ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
            $item = $this->coreFunctions->opentable($qry, [$rows['uom'], $rows['itemid']]);
            $factor = 1;
            if (!empty($item)) {
                $item[0]->factor = $this->othersClass->val($item[0]->factor);
                if ($item[0]->factor !== 0) $factor = $item[0]->factor;
            }

            $rows['rrcost'] = $this->othersClass->sanitizekeyfield('amt',  $rows['rrcost']);
            $rows['rrqty'] = $this->othersClass->sanitizekeyfield('qty',  $rows['rrqty']);

            $computedata = $this->othersClass->computestock($rows['rrcost'], $rows['disc'], $rows['rrqty'], $factor);

            $stock = [
                'rrcost' => $rows['rrcost'],
                'cost' => $computedata['amt'],
                'ext' => $computedata['ext'],
                'editby' =>  $config['params']['user'],
                'editdate' => $this->othersClass->getCurrentTimeStamp()
            ];
            foreach ($stock as $key2 => $value) {
                $stock[$key2] = $this->othersClass->sanitizekeyfield($key2, $value);
            }

            $this->coreFunctions->sbcupdate('hcdstock', $stock, ['trno' => $rows['trno'], 'line' => $rows['line']]);

            $deleterow = true;

            if ($canvastats == 1) {
                $this->coreFunctions->execqry("update hprstock set uom='" . $rows['uom'] . "' where trno=" . $rows['refx'] . " and line=" . $rows['linex'], 'update');
                $this->coreFunctions->execqry("update hstockinfotrans set uom2='" . $rows['uom2'] . "',uom3='" . $rows['uom3'] . "' where trno=" . $rows['refx'] . " and line=" . $rows['linex'], 'update');
                $prdata = $this->coreFunctions->opentable("select pr.itemid, pr.rrqty, pr.rrcost, pr.uom, ifnull(uom.factor,0), info.uom2, info.uom3, ifnull(uom2.factor,0) as factor2, ifnull(uom3.factor,0) as factor3
                                                    from hprstock as pr left join uom on uom.itemid=pr.itemid and uom.uom=pr.uom 
                                                    left join hstockinfotrans as info on info.trno=pr.trno and info.line=pr.line
                                                    left join uomlist as uom2 on uom2.uom=info.uom2 and uom2.isconvert=1
                                                    left join uomlist as uom3 on uom3.uom=info.uom3 and uom3.isconvert=1
                                                    where pr.trno=? and pr.line=?", [$rows['refx'], $rows['linex']]);
                if (!empty($prdata)) {
                    if ($prdata[0]->uom3 != '') {
                        if ($prdata[0]->factor3 == 0) {
                            $prdata[0]->factor3 = 1;
                        }

                        $computeprdata = $this->othersClass->computestock($prdata[0]->rrcost, '', $prdata[0]->rrqty, $prdata[0]->factor3);
                        $prdataupdate = [
                            'editby' =>  $config['params']['user'],
                            'editdate' => $this->othersClass->getCurrentTimeStamp(),
                            'qty' => $computeprdata['qty'],
                            'cost' => $computeprdata['amt'],
                            'ext' =>  round($computeprdata['ext'], $this->companysetup->getdecimal('qty', $config['params']))
                        ];

                        $this->coreFunctions->sbcupdate("hprstock", $prdataupdate, ['trno' => $rows['refx'], 'line' => $rows['linex']]);
                    }
                }
                $this->coreFunctions->execqry("update hprstock set statrem='Canvass Sheet - Approved',statdate='" . $this->othersClass->getCurrentTimeStamp() . "' where trno=? and line=?", 'update', [$rows['reqtrno'], $rows['reqline']]);

                $qry1 = "select stock.qty from cdhead as head left join cdstock as stock on stock.trno=head.trno where head.doc='CD' and stock.void=0 and stock.status=1 and stock.refx=" . $rows['refx'] . " and stock.linex=" . $rows['linex'];
                $qry1 = $qry1 . " union all select stock.qty from hcdhead as head left join hcdstock as stock on stock.trno=head.trno where head.doc='CD' and stock.void=0 and stock.status=1 and stock.refx=" . $rows['refx'] . " and stock.linex=" . $rows['linex'];
                $qry2 = "select ifnull(sum(qty),0) as value from (" . $qry1 . ") as t";
                $cdqa = $this->coreFunctions->datareader($qry2);
                if ($cdqa == '') {
                    $cdqa = 0;
                }
                $this->coreFunctions->execqry("update hprstock set cdqa=" . $cdqa . " where trno=" . $rows['refx'] . " and line=" . $rows['linex']);

                $this->logger->sbcwritelog($rows['trno'], $config, 'STOCK', 'Line: ' . $rows['line'] . ' - APPROVED item ');
                $stockinfo = [
                    'amt1' => $rows['amt1'],
                    'amt2' => $rows['amt2'],
                    'editby' =>  $config['params']['user'],
                    'editdate' => $this->othersClass->getCurrentTimeStamp(),
                ];

                foreach ($stockinfo as $key2 => $value) {
                    $stockinfo[$key2] = $this->othersClass->sanitizekeyfield($key2, $value);
                }
                $this->coreFunctions->sbcupdate('hstockinfotrans', $stockinfo, ['trno' => $rows['trno'], 'line' => $rows['line']]);
            }
        } else {
            ExitHere:
            $status = false;
            if ($canvastats == 1) {
                $msg = "Canvass for item " . $rows['itemname'] . " was already approved. ";
            } else {
                $msg = "Canvass for item " . $rows['itemname'] . " was already rejected. ";
            }
        }

        $data = $this->loaddata($config);
        return ['msg' => $msg, 'status' => $status, 'data' => $data, 'deleterow' => $deleterow];
    }
    public function disapprovedreq($config)
    {
        $msg = "Canvass for item was rejected. ";
        $row = $config['params']['row'];
        $rows = [];
        $status = true;

        foreach ($row as $key2 => $value) {
            $rows[$key2] = $this->othersClass->sanitizekeyfield($key2, $value);
        }
        $stats = $this->coreFunctions->getfieldvalue("hcdstock", "status", 'trno = ? and line = ?', [$rows['trno'], $rows['line']]);

        if ($rows['status'] == 0) {
            if ($stats != 0) {
                goto ExitHere;
            }
            $current_timestamp = $this->othersClass->getCurrentTimeStamp();
            $qry = "update hcdstock set status=?, approveddate=?,approvedby=?,rem=?  where trno=? and line=?";

            $rows['status'] = 2;
            $this->coreFunctions->execqry($qry, 'update', [$rows['status'], $current_timestamp, $config['params']['user'], $rows['rem'], $rows['trno'], $rows['line']]);
            $qry = "select ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
            $item = $this->coreFunctions->opentable($qry, [$rows['uom'], $rows['itemid']]);
            $factor = 1;
            if (!empty($item)) {
                $item[0]->factor = $this->othersClass->val($item[0]->factor);
                if ($item[0]->factor !== 0) $factor = $item[0]->factor;
            }

            $rows['rrcost'] = $this->othersClass->sanitizekeyfield('amt',  $rows['rrcost']);
            $rows['rrqty'] = $this->othersClass->sanitizekeyfield('qty',  $rows['rrqty']);

            $computedata = $this->othersClass->computestock($rows['rrcost'], $rows['disc'], $rows['rrqty'], $factor);

            $stock = [
                'rrcost' => $rows['rrcost'],
                'cost' => $computedata['amt'],
                'ext' => $computedata['ext'],
                'editby' =>  $config['params']['user'],
                'editdate' => $this->othersClass->getCurrentTimeStamp()
            ];
            foreach ($stock as $key2 => $value) {
                $stock[$key2] = $this->othersClass->sanitizekeyfield($key2, $value);
            }

            $this->coreFunctions->sbcupdate('hcdstock', $stock, ['trno' => $rows['trno'], 'line' => $rows['line']]);
            if ($rows['status'] == 2) {
                $pending = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from hcdstock where trno=? and status<>2", [$rows['trno']], '', true);
                if ($pending == 0) $this->coreFunctions->execqry("update transnum set statid=77 where trno=" . $rows['trno']);
            }
            if ($rows['status'] == 2) {
                $this->coreFunctions->execqry("update hprstock set statrem='Canvass Sheet - Rejected',statdate='" . $this->othersClass->getCurrentTimeStamp() . "' where trno=? and line=? and statrem<>'Canvass Sheet - Approved'", 'update', [$rows['reqtrno'], $rows['reqline']]);
                $this->logger->sbcwritelog($rows['trno'], $config, 'STOCK', 'Line: ' . $rows['line'] . ' - REJECTED item ');
            }
        } else {
            ExitHere:
            $status = false;
            if ($stats == 1) {
                $msg = "Canvass for item " . $rows['itemname'] . " was already approved. ";
            } else {
                $msg = "Canvass for item " . $rows['itemname'] . " was already rejected. ";
            }
        }

        $data = $this->loaddata($config);
        return ['msg' => $msg, 'status' => $status, 'data' => $data, 'deleterow' => true];
    }
    public function save($config)
    {
        $data = [];
        $stockinfo = [];
        $row = $config['params']['row'];
        $update = true;
        if ($update) {
            unset($this->fields[1]);
            unset($this->fields[2]);
        }
        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }
        if (isset($row['carem'])) {
            $stockinfo['carem'] = $row['carem'];
        }
        foreach ($stockinfo as $key2 => $value) {
            $stockinfo[$key2] = $this->othersClass->sanitizekeyfield($key2, $value);
        }

        $data['editby'] = $config['params']['user'];
        $data['editdate'] =  $this->othersClass->getCurrentTimeStamp();
        $this->coreFunctions->sbcupdate('hstockinfotrans', $stockinfo, ['trno' => $row['trno'], 'line' => $row['line']]);
        $this->coreFunctions->sbcupdate($this->hstock, $data, ['line' => $row['line'], 'trno' => $row['trno']]);
        $returnrow = $this->loaddataperrecord($config, $row['trno'], $row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
    } //end function

    private function loaddataperrecord($config, $trno, $line)
    {
        $select = $this->selectqry($config);
        $arrayfilter = [$trno, $line];
        $query = "select " . $select . " 
  from hcdstock as stock
  left join hcdhead as head on head.trno = stock.trno
  left join item on item.itemid=stock.itemid
  left join headinfotrans as headinfo on headinfo.trno=head.trno
  left join hstockinfotrans as si on si.trno=stock.trno and si.line=stock.line
  left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
  left join model_masterfile as mm on mm.model_id = item.model 
  left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
  left join client as warehouse on warehouse.clientid=stock.whid 
  left join client as cust on cust.clientid=stock.suppid 
  left join clientsano as sa on sa.line=stock.sano 
  left join client as dept on dept.clientid=stock.deptid
  left join reqcategory as cat on cat.line=stock.catid
  left join duration as d on d.line=info.durationid
  left join uomlist as uom3 on uom3.uom=si.uom3 and uom3.isconvert=1
  left join uomlist as uom2 on uom2.uom=si.uom2 and uom2.isconvert=1
  left join transnum as num on num.trno=head.trno
  where stock.void = 0 and stock.status=0 and stock.trno = ? and stock.line = ?  order by head.docno";

        $data = $this->coreFunctions->opentable($query, $arrayfilter);
        return $data;
    }

    public function loaddata($config)
    {
        $center = $config['params']['center'];
        $select = $this->selectqry($config);
        $query = "select " . $select . " 
                      from hcdstock as stock
          left join hcdhead as head on head.trno = stock.trno
          left join item on item.itemid=stock.itemid
          left join headinfotrans as headinfo on headinfo.trno=head.trno
          left join hstockinfotrans as si on si.trno=stock.trno and si.line=stock.line
          left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
          left join model_masterfile as mm on mm.model_id = item.model 
          left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
          left join client as warehouse on warehouse.clientid=stock.whid 
          left join client as cust on cust.clientid=stock.suppid 
          left join clientsano as sa on sa.line=stock.sano 
          left join client as dept on dept.clientid=stock.deptid
          left join reqcategory as cat on cat.line=stock.catid
          left join duration as d on d.line=info.durationid
          left join uomlist as uom3 on uom3.uom=si.uom3 and uom3.isconvert=1
          left join uomlist as uom2 on uom2.uom=si.uom2 and uom2.isconvert=1
           where  stock.void=0 and stock.status= 0 and stock.ismanual=0  order by info.ctrlno, head.docno ";
        $data = $this->coreFunctions->opentable($query);
        return $data;
    }
} //end class
