<?php

namespace App\Http\Classes\modules\tableentry;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use Datetime;
use DateInterval;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\lookup\constructionlookup;
use App\Http\Classes\lookup\warehousinglookup;

class  tabstockinfo
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'STOCK INFO';
    public $tablenum = 'transnum';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'stockinfotrans';
    private $htable = 'hstockinfotrans';
    private $othersClass;
    public $style = 'width:100%;max-width: 100%';
    private $fields = ['trno', 'line', 'rem', 'itemdesc', 'purpose', 'specs', 'durationid', 'dateneeded', 'isasset', 'unit'];
    private $Stocksfields = ['trno', 'line', 'status', 'suppid'];
    public $showclosebtn = true;
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';
    private $constructionlookup;
    private $sqlquery;
    private $logger;
    private $warehousinglookup;


    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->constructionlookup = new constructionlookup;
        $this->sqlquery = new sqlquery;
        $this->logger = new Logger;
        $this->warehousinglookup = new warehousinglookup;
    }

    public function getAttrib()
    {
        $attrib = [
            'load' => 0
        ];
        return $attrib;
    }

    public function createTab($config)
    {
        // $action = 0;
        $barcode = 0;
        $rrqty = 1;
        $unit = 2;
        $uom = 3;
        $rrcost = 4;
        $ext = 5;
        $itemdesc = 6;
        $rem = 7;
        $purpose = 8;
        $empname = 9;
        $stat = 10;
        $dateneeded = 11;
        $specs = 12;
        $duration = 13;
        $deadline = 14;
        $isasset = 15;


        $tab = [$this->gridname => ['gridcolumns' => ['barcode', 'rrqty', 'unit', 'uom', 'rrcost', 'ext', 'itemdesc', 'rem', 'purpose', 'empname', 'stat', 'dateneeded', 'specs', 'duration', 'deadline', 'isasset']]];

        $stockbuttons = ['save'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$itemdesc]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $obj[0][$this->gridname]['columns'][$rem]['style'] = 'width:120px;whiteSpace: normal;min-width:200px;';
        $obj[0][$this->gridname]['columns'][$purpose]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $obj[0][$this->gridname]['columns'][$dateneeded]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $obj[0][$this->gridname]['columns'][$specs]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $obj[0][$this->gridname]['columns'][$barcode]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $obj[0][$this->gridname]['columns'][$empname]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px';
        $obj[0][$this->gridname]['columns'][$isasset]['style'] = 'width: 100px;whiteSpace: normal;min-width:150px;max-width:100px';

        $obj[0][$this->gridname]['columns'][$rem]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$purpose]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dateneeded]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$barcode]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$deadline]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$duration]['type'] = 'lookup';
        $obj[0][$this->gridname]['columns'][$duration]['action'] = 'lookupsetup';
        $obj[0][$this->gridname]['columns'][$duration]['lookupclass'] = 'lookupduration';

        $obj[0][$this->gridname]['columns'][$unit]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$unit]['readonly'] = false;

        $obj[0][$this->gridname]['columns'][$isasset]['action'] = 'lookupsetup';

        $obj[0][$this->gridname]['columns'][$stat]['label'] = 'Status';
        $obj[0][$this->gridname]['columns'][$stat]['type'] = 'lookup';
        $obj[0][$this->gridname]['columns'][$stat]['action'] = 'lookupsetup';
        $obj[0][$this->gridname]['columns'][$stat]['lookupclass'] = 'lookupitemstatus';
        $obj[0][$this->gridname]['columns'][$stat]['style'] = 'width:150px;whiteSpace: normal;min-width:100px;';

        $obj[0][$this->gridname]['columns'][$empname]['label'] = 'Assigned User';
        $obj[0][$this->gridname]['columns'][$empname]['type'] = 'lookup';
        $obj[0][$this->gridname]['columns'][$empname]['action'] = 'lookupsetup';
        $obj[0][$this->gridname]['columns'][$empname]['lookupclass'] = 'lookupemployeepo';
        $obj[0][$this->gridname]['columns'][$rrqty]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$ext]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$rrcost]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$uom]['type'] = 'coldel';
        if ($config['params']['companyid'] == 16) { //ati
            if ($config['params']['doc'] == 'PR') {
                $obj[0][$this->gridname]['columns'][$unit]['label'] = 'Temp. UOM';
            }

            if ($config['params']['doc'] == 'CD') {
                $obj[0][$this->gridname]['columns'][$empname]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$stat]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$duration]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$isasset]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$rrqty]['type'] = 'input';
                $obj[0][$this->gridname]['columns'][$ext]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$rrcost]['type'] = 'label';

                $obj[0][$this->gridname]['columns'][$unit]['type'] = 'coldel';
                $obj[0][$this->gridname]['columns'][$uom]['type'] = 'lookup';
                $obj[0][$this->gridname]['columns'][$uom]['action'] = 'lookupsetup';
                $obj[0][$this->gridname]['columns'][$uom]['lookupclass'] = 'lookupuom';
            }
        }
        $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $trno = $config['params']['tableid'];
        $isposted = $this->othersClass->isposted2($trno, 'transnum');
        if ($isposted) {
            $tbuttons = ['saveallentry'];
        } else {
            $tbuttons = [];
        }

        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }


    public function lookupsetup($config)
    {

        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'lookupduration':
                return $this->lookupduration($config);
                break;
            case 'lookupitemstatus':
                return $this->lookupitemstat($config);
                break;
            case 'lookupemployeepo':
                return $this->lookupemployeepo($config);
                break;
            case 'lookupisasset':
                return $this->lookupisasset($config);
                break;
            case 'lookupuom':
                return  $this->lookupuom($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under PR documents'];
                break;
        }
    }

    public function lookupcallback($config)
    {
    }
    public function  lookupuom($config)
    {
        $row = $config['params']['row'];
        $plotting = array('uom' => 'uom');
        $plottype = 'plotgrid';
        $title = 'List of Unit of Measurement';
        $lookupsetup = array(
            'type' => 'single',
            'title' => $title,
            'style' => 'width:900px;max-width:900px;'
        );
        $plotsetup = array(
            'plottype' => $plottype,
            'action' => '',
            'plotting' => $plotting
        );
        $cols = [
            ['name' => 'uom', 'label' => 'Unit of Measurement', 'align' => 'left', 'field' => 'uom', 'sortable' => true, 'style' => 'font-size:16px;'],
        ];
        $qry = "select uom from uom where itemid=? and isinactive = 0 and itemid<>0";
        $data = $this->coreFunctions->opentable($qry, [$row['itemid']]);
        $index = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
    }

    public function lookupduration($config)
    {
        //default
        $trno = $config['params']['tableid'];
        $plotting = array('durationid' => 'line', 'duration' => 'duration');
        $plottype = 'plotgrid';
        $title = 'LIST OF DURATION';
        $lookupsetup = array(
            'type' => 'single',
            'title' => $title,
            'style' => 'width:900px;max-width:900px;'
        );
        $plotsetup = array(
            'plottype' => $plottype,
            'action' => '',
            'plotting' => $plotting
        );
        // lookup columns
        $cols = [
            ['name' => 'duration', 'label' => 'Duration', 'align' => 'left', 'field' => 'duration', 'sortable' => true, 'style' => 'font-size:16px;'],
        ];

        $qry = "select line, duration from duration order by duration";
        $data = $this->coreFunctions->opentable($qry);
        $index = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
    } // end function

    public function lookupitemstat($config)
    {
        //default
        $trno = $config['params']['tableid'];
        $title = 'Item Status';
        $plottype = 'plotgrid';
        $qry = "select 0 as line, '' as stat union all select line, status as stat from trxstatus where doc='ITEMS'  order by stat";
        $plotting = array('status' => 'line', 'stat' => 'stat');


        $lookupsetup = array(
            'type' => 'single',
            'title' => $title,
            'style' => 'width:900px;max-width:900px;'
        );

        $plotsetup = array(
            'plottype' => $plottype,
            'action' => '',
            'plotting' => $plotting
        );
        // lookup columns
        $cols = [
            ['name' => 'stat', 'label' => 'Status', 'align' => 'left', 'field' => 'stat', 'sortable' => true, 'style' => 'font-size:16px;'],
        ];

        $data = $this->coreFunctions->opentable($qry);
        $index = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
    } // end function

    public function lookupemployeepo($config)
    {
        //default
        $trno = $config['params']['tableid'];

        $title = 'List of Employee';
        $plotting = array('suppid' => 'clientid',  'empname' => 'clientname');
        $plottype = 'plotgrid';

        $qry = "select client.clientid,client.client,client.clientname,client.addr,client.wh,ifnull(f.cur,'P') as cur,
            ifnull(f.curtopeso,1) as forex,client.terms,ifnull(a.client,'') as agentcode, 
            ifnull(a.clientname,'') as agentname, client.shipid, client.billid, client.tin, client.billcontactid,client.shipcontactid,
            ifnull(client.deptid, 0) as deptid,
            ifnull(dept.client, ' ') as dept,
            ifnull(dept.clientname, ' ') as deptname,client.position,client.groupid
            , client.tel2 as tel2, client.vattype, case when client.vattype='Vat-registered' then 12 else 0 end as tax
            from client 
            left join forex_masterfile as f on f.line = client.forexid 
            left join client as a on a.client = client.agent 
            left join client as dept on dept.clientid = client.deptid
            left join employee on employee.empid = client.clientid
            left join client as dc on dc.clientid=employee.deptid where client.isemployee=1 and client.email<>''";

        $lookupsetup = array(
            'type' => 'single',
            'title' => $title,
            'style' => 'width:900px;max-width:900px;'
        );

        $plotsetup = array(
            'plottype' => $plottype,
            'action' => '',
            'plotting' => $plotting
        );
        // lookup columns
        $cols = array();
        array_push($cols, array('name' => 'client', 'label' => 'Customer Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'));
        array_push($cols, array('name' => 'clientname', 'label' => 'Customer Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'));
        array_push($cols, array('name' => 'addr', 'label' => 'Address', 'align' => 'left', 'field' => 'addr', 'sortable' => true, 'style' => 'font-size:16px; width:100px;'));

        $data = $this->coreFunctions->opentable($qry);
        $index = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
    } // end function

    public function lookupisasset($config)
    {
        //default
        $trno = $config['params']['tableid'];
        $title = 'Is Asset?';
        $plottype = 'plotgrid';
        $qry = "select '' as field1 
        union all 
        select 'YES' as field1
        union all 
        select 'NO' as field1";
        $plotting = array('isasset' => 'field1');

        $lookupsetup = array(
            'type' => 'single',
            'title' => $title,
            'style' => 'width:900px;max-width:900px;'
        );

        $plotsetup = array(
            'plottype' => $plottype,
            'action' => '',
            'plotting' => $plotting
        );
        // lookup columns
        $cols = [
            ['name' => 'field1', 'label' => 'Is Asset?', 'align' => 'left', 'field' => 'field1', 'sortable' => true, 'style' => 'font-size:16px;']
        ];
        $data = $this->coreFunctions->opentable($qry);
        $index = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
    } // end function

    public function save($config)
    {
        $trno = $config['params']['tableid'];
        $data = [];
        $row = $config['params']['row'];
        $data['trno'] = $config['params']['tableid'];

        foreach ($this->fields as $key2 => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];

        if ($data['durationid'] != 0) {
            $durationdays = $this->coreFunctions->getfieldvalue("duration", "days", "line=?", [$data['durationid']]);
            $daterequested = $this->coreFunctions->getfieldvalue('hprhead', "dateid", "trno=?", [$trno]);

            $newDate = new DateTime($daterequested);
            $days = new DateInterval('P' . $durationdays . 'D');
            $newDate->add($days);
            $data['deadline']  = $newDate->format('Y-m-d');
        } else {
            $data['deadline']  = null;
        }

        if ($this->coreFunctions->sbcupdate($this->htable, $data, ['trno' => $trno, 'line' => $row['line']]) == 1) {
            $returnrow = $this->loaddataperrecord($config, $row['line']);
            return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow, 'reloadhead' => true];
        } else {
            return ['status' => false, 'msg' => 'Saving failed.'];
        }
    } //end function

    public function saveallentry($config)
    {
        $trno = $config['params']['tableid'];
        $data = $config['params']['data'];
        $doc = $config['params']['doc'];
        foreach ($data as $key => $value) {
            $data2 = [];
            $dataStock = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }

                foreach ($this->Stocksfields as $key2 => $value2) {
                    $dataStock[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }

                $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                $data2['editby'] = $config['params']['user'];

                $dataStock['editdate'] = $this->othersClass->getCurrentTimeStamp();
                $dataStock['editby'] = $config['params']['user'];

                switch ($doc) {
                    case 'PR':
                        $hstock = "hprstock";
                        if ($data2['durationid'] != 0) {
                            $durationdays = $this->coreFunctions->getfieldvalue("duration", "days", "line=?", [$data2['durationid']]);
                            $daterequested = $this->coreFunctions->getfieldvalue('hprhead', "dateid", "trno=?", [$trno]);

                            $newDate = new DateTime($daterequested);
                            $days = new DateInterval('P' . $durationdays . 'D');
                            $newDate->add($days);
                            $data2['deadline']  = $newDate->format('Y-m-d');
                        } else {
                            $data2['deadline']  = null;
                        }
                        break;
                    case 'CD':
                        $hstock = "hcdstock";
                        $dataStock['uom'] = $data[$key]['uom'];
                        $dataStock['rrqty'] = $data[$key]['rrqty'];
                        $stockstatus = $this->coreFunctions->datareader("select stock.status as value from hcdstock as stock
                     where stock.trno = ? and stock.line = ? and stock.status <> 0 and stock.approveddate is not null limit 1", [$trno, $data[$key]['line']]);

                        if ($stockstatus != '') {
                            return ['status' => false, 'msg' => 'Save Item FAILED, Item has already been approved. You are not allowed to modify.'];
                        }
                        $qry = "select ifnull(item.barcode,'') as barcode, ifnull(item.itemname,0) as itemname,ifnull(uom.factor,1) as factor from item 
                    left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
                        $item = $this->coreFunctions->opentable($qry, [$data[$key]['uom'], $data[$key]['itemid']]);
                        $factor = 1;
                        if (!empty($item)) {
                            $item[0]->factor = $this->othersClass->val($item[0]->factor);
                            if ($item[0]->factor !== 0) $factor = $item[0]->factor;
                        }
                        $forex = $this->coreFunctions->getfieldvalue('hcdhead', 'forex', 'trno=?', [$trno]);
                        $computedata = $this->othersClass->computestock($data[$key]['rrcost'], $data[$key]['disc'], $data[$key]['rrqty'], $factor);
                        $ext = round($computedata['ext'], $this->companysetup->getdecimal('qty', $config['params']));
                        $dataStock['ext'] = $ext;
                        $dataStock['cost'] = $computedata['amt'] * $forex;
                        $dataStock['qty'] = $computedata['qty'];
                        break;
                }

                if ($data[$key]['line'] != 0) {
                    $this->coreFunctions->sbcupdate($this->htable, $data2, ['trno' => $data[$key]['trno'], 'line' => $data[$key]['line']]);
                    $this->coreFunctions->sbcupdate($hstock, $dataStock, ['trno' => $data[$key]['trno'], 'line' => $data[$key]['line']]);
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata, 'reloadhead' => true];
    } // end function $$


    private function selectqry($config)
    {
        $doc = $config['params']['doc'];
        switch ($doc) {
            case 'CD':
                return " select si.trno,si.line,stock.rem,info.itemdesc2 as itemdesc,info.purpose,info.specs2 as specs,info.durationid,left(info.dateneeded,10) as dateneeded,
                                d.duration,d.days,i.barcode,date(info.deadline) as deadline,stock.status,    
                                case when stock.status = 0 then 'Pending'
                                when stock.status = 1 then 'Approved'
                                when stock.status = 2 then 'Rejected'
                                end as stat, stock.suppid, ifnull(emp.clientname,'') as empname,info.isasset,si.unit, FORMAT(stock.ext,5) as ext,
                                    FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as rrqty,stock.rrcost,stock.disc,stock.itemid,stock.uom";
                break;

            case 'PR':
                return " select si.trno,si.line,si.rem,si.itemdesc,si.purpose,si.specs,si.durationid,left(si.dateneeded,10) as dateneeded,d.duration,d.days,i.barcode,date(si.deadline) as deadline,
                                stock.status, ifnull(stat.status,'') as stat, stock.suppid, ifnull(emp.clientname,'') as empname,si.isasset,si.unit";
                break;
        }
    }

    private function loaddataperrecord($config)
    {

        $trno = $config['params']['row']['trno'];
        $line = $config['params']['row']['line'];
        $doc = $config['params']['doc'];
        $sqlselect = $this->selectqry($config);
        switch ($doc) {
            case 'CD':
                $qry = $sqlselect . ",'' as bgcolor from " . $this->htable . " as si
                        left join hcdstock as stock on stock.trno=si.trno and stock.line=si.line
                        left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
                        left join duration as d on d.line=info.durationid
                        left join item as i on i.itemid=stock.itemid
                        left join trxstatus as stat on stat.line=stock.status
                        left join client as emp on emp.clientid=stock.suppid 
                        where si.trno = ? and si.line = ? and stock.approveddate is null and stock.status = 0 order by line";
                break;

            case 'PR':
                $qry = $sqlselect . ",'' as bgcolor from " . $this->htable . " as si
                        left join duration as d on d.line=si.durationid
                        left join hprstock as stock on stock.trno=si.trno and stock.line=si.line
                        left join item as i on i.itemid=stock.itemid
                        left join trxstatus as stat on stat.line=stock.status
                        left join client as emp on emp.clientid=stock.suppid 
                        where si.trno = ? and si.line = ? order by line";
                break;
        }

        $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
        return $data;
    }

    public function loaddata($config)
    {
        $trno = $config['params']['tableid'];
        $doc = $config['params']['doc'];
        $sqlselect = $this->selectqry($config);

        switch ($doc) {
            case 'CD':
                $qry = $sqlselect . ",'' as bgcolor from " . $this->htable . " as si
                   
              
                left join hcdstock as stock on stock.trno=si.trno and stock.line=si.line
                 left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
                   left join duration as d on d.line=info.durationid
                left join item as i on i.itemid=stock.itemid
                left join trxstatus as stat on stat.line=stock.status
                left join client as emp on emp.clientid=stock.suppid 
                where si.trno = ? and stock.approveddate is null and stock.status = 0
                order by si.line";
                break;
            case 'PR':
                $qry = $sqlselect . ",'' as bgcolor from " . $this->htable . " as si
                left join duration as d on d.line=si.durationid
                left join hprstock as stock on stock.trno=si.trno and stock.line=si.line
                left join item as i on i.itemid=stock.itemid
                left join trxstatus as stat on stat.line=stock.status
                left join client as emp on emp.clientid=stock.suppid 
                where si.trno = ? 
                order by si.line";
                break;
        }
        $data = $this->coreFunctions->opentable($qry, [$trno]);
        return $data;
    }
} //end class
