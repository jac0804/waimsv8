<?php

namespace App\Http\Classes\modules\autoserventry;

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

class entryamparts
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'PARTS / ITEMS';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'lastock';
    private $htable = 'glstock';
    private $othersClass;
    public $style = 'width:100%;max-width:1000px;';
    public $tablenum = 'cntnum';
    public $tablelogs = 'table_log';
    public $htablelogs = 'htable_log';
    public $tablelogs_del = 'del_table_log';
    public $dqty = 'isqty';
    public $hqty = 'iss';
    private $fields = ['trno', 'itemid', 'taskline', 'jobline', 'uom', 'ext', 'rem', 'isamt', 'isqty', 'disc', 'whid'];
    public $showclosebtn = false;
    private $enrollmentlookup;
    private $logger;

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
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createTab($config)
    {
        $descriptions = isset($config['params']['row']['description']) ? $config['params']['row']['description'] : '';
        $columns = ['subcode', 'task', 'jobcode', 'jobtitle', 'barcode', 'isqty', 'uom', 'itemname', 'whname', 'isamt', 'disc', 'ext', 'rem']; // 'packname'

        if ($descriptions != '') {
            $columns = ['action',  'barcode', 'isqty', 'uom', 'itemname', 'whname', 'isamt', 'disc', 'ext', 'rem']; // 'packname'
        }
        $tab = [$this->gridname => ['gridcolumns' => $columns]];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        // var_dump($descriptions);

        $stockbuttons = []; //viewing
        if ($descriptions != '') {
            $stockbuttons = ['save', 'delete'];
        }

        $tab = [$this->gridname => ['gridcolumns' => $columns]];
        $obj = $this->tabClass->createTab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$barcode]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";

        $obj[0][$this->gridname]['columns'][$rem]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
        $obj[0][$this->gridname]['columns'][$barcode]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$barcode]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$barcode]['label'] = 'Product Id';
        $obj[0][$this->gridname]['columns'][$itemname]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$itemname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$itemname]['label'] = 'Product Name';
        if ($descriptions != '') {
            $this->modulename .= ' - ' . $descriptions;
            $obj[0][$this->gridname]['columns'][$action]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
            $obj[0][$this->gridname]['columns'][1]['btns']['delete']['label'] = 'delete';
            $obj[0][$this->gridname]['columns'][$itemname]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        } else {
            $obj[0][$this->gridname]['columns'][$isqty]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$isamt]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$rem]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$uom]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$subcode]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$whname]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$disc]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$subcode]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
            $obj[0][$this->gridname]['columns'][$subcode]['label'] = 'Task Code';
            $obj[0][$this->gridname]['columns'][$task]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$task]['label'] = 'Task Name';
            $obj[0][$this->gridname]['columns'][$jobtitle]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$jobcode]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$jobtitle]['label'] = 'Job Description';

            $obj[0][$this->gridname]['columns'][$isqty]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; text-align: left';
            $obj[0][$this->gridname]['columns'][$uom]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; text-align: left';
            $obj[0][$this->gridname]['columns'][$itemname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px; text-align: left';
            $obj[0][$this->gridname]['columns'][$whname]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; text-align: left';
            $obj[0][$this->gridname]['columns'][$disc]['style'] = 'width:50px;whiteSpace: normal;min-width:50px; text-align: left';

            $obj[0][$this->gridname]['columns'][$ext]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; text-align: right';

            $obj[0][$this->gridname]['columns'][$rem]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; text-align: left';

            $obj[0][$this->gridname]['columns'][$subcode]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; text-align: center';

            $obj[0][$this->gridname]['columns'][$task]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; text-align: left';

            $obj[0][$this->gridname]['columns'][$jobtitle]['style'] = 'width:200px;whiteSpace: normal;min-width:200px; text-align: left';
            $obj[0][$this->gridname]['columns'][$jobcode]['style'] = 'width:80px;whiteSpace: normal;min-width:80px; text-align: left';
        }





        $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $rows = isset($config['params']['row']) ? $config['params']['row'] : '';
        if ($rows != '') {
            $tbuttons = ['addoutlet', 'saveallentry'];
            $obj = $this->tabClass->createtabbutton($tbuttons);
            $obj[0]['lookupclass'] = 'addparts';
            $obj[0]['action'] = 'lookupsetup';
        } else {
            //viewing
            $tbuttons = [];
            $obj = $this->tabClass->createtabbutton($tbuttons);
        }
        return $obj;
    }

    public function loaddata($config)
    {

        // $row = isset($config['params']['sourcerow']) ? $config['params']['sourcerow'] :  $config['params']['row'] ;
        $row = isset($config['params']['sourcerow']) ? $config['params']['sourcerow'] : (isset($config['params']['row']) ? $config['params']['row'] : 0);
        $condition = "";
        $join = "";
        $hjoin = "";
        $orderby = " order by line";
        $fieldh = "";
        if ($row != 0) {
            $jobline = $row['jobline'];
            $taskline = $row['taskline'];
            $condition = " and stock.jobline = $jobline and stock.taskline = $taskline";
        } else {
            $join = " left join amtask as jt on jt.line=stock.taskline
             left join jobtask as jj on jj.line=jt.laborline
             left join amjobs as jo on jo.line=jt.jobline and jo.trno=jt.trno
             left join jobthead as joo on joo.line=jo.jobid ";
            $hjoin = " left join hamtask as jt on jt.line=stock.taskline
             left join jobtask as jj on jj.line=jt.laborline
             left join hamjobs as jo on jo.line=jt.jobline and jo.trno=jt.trno
             left join jobthead as joo on joo.line=jo.jobid ";
            $fieldh = ", jj.code as subcode, jj.description as task,joo.jobtitle, joo.docno as jobcode";
            $orderby = " order by jobcode";
        }
        $trno = $config['params']['tableid'];
        $filtersearch = "";
        $searchfield  = $this->fields;

        if (isset($config['params']['filter'])) {
            $search = $config['params']['filter'];
            foreach ($searchfield as $sfield) {
                if ($filtersearch == "") {
                    $filtersearch .= " and (" . $sfield . " like '%" . $search . "%'";
                } else {
                    $filtersearch .= " or " . $sfield . " like '%" . $search . "%'";
                }
            }
            $filtersearch .= ")";
        }
        $select = $this->selectqry() . " $fieldh , '' as bgcolor";
        $qry = "select " . $select . " from " . $this->table . " as stock
        left join item on item.itemid=stock.itemid
        left join client as wh on wh.clientid=stock.whid
        left join location on location.line=stock.locid
        $join
        where stock.trno = ? $condition $filtersearch 
        
        union all

        select " . $select . " from " . $this->htable . " as stock
        left join item on item.itemid=stock.itemid
        left join client as wh on wh.clientid=stock.whid
        left join location on location.line=stock.locid
        $hjoin
        where stock.trno = ? $condition $filtersearch 

        $orderby";

        $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
        return $data;
    }

    private function selectqry()
    {
        $query = "stock.line,stock.jobline,stock.taskline,stock.amt,item.barcode,item.itemname,item.uom,
        format(stock.isqty,2) as isqty,stock.iss,stock.disc,stock.itemid,stock.uom,
        stock.trno,stock.rem,format(stock.ext,2) as ext,format(stock.isamt,2) as isamt,
        wh.clientname as whname, stock.whid,stock.locid";
        return $query;
    }

    public function add($config)
    {
        $data = [];
        return $data;
    }

    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        $trno = $config['params']['tableid'];
        $doc = $config['params']['doc'];
        $tableid = $config['params']['tableid'];

        $dateTables = ['lastock'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], 0, [], false, $dateTables);
        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    // $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                    $data2[$value2] = $this->othersClass->sanitizekeyfieldFast($value2, $data[$key][$value2], $lookups);
                }
                $computedata = $this->computepartsprice($config, $data[$key]);

                $data2['ext'] = $computedata['ext'];
                $data2['amt'] = $computedata['amt'];
                $data2['iss'] = $computedata['qty'];
                $data2['isqty'] = $data[$key]['isqty'];

                // if ($data[$key]['line'] == 0) {
                //     $data2['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
                //     $data2['encodedby'] = $config['params']['user'];

                //     $line = $this->coreFunctions->insertGetId($this->table, $data2);
                //     $config['params']['doc'] = 'ENTRYAMPARTS';

                $item = $this->coreFunctions->opentable("select barcode,itemname from item where itemid =?", [$data2['itemid']]);
                //     $this->logger->sbcmasterlog($line, $config, ' CREATE - Line: ' . $line . 'Job Code :' . $item[0]->barcode . ' ' . 'Job Desc : ' . $item[0]->itemname);
                // } else {
                $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                $data2['editby'] = $config['params']['user'];
                $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line'], 'trno' => $trno]);
                // }

                $logs = [
                    'barcode' => $item[0]->barcode,
                    'amt' => $data2['isamt'],
                    'disc' => $data2['disc'],
                    'qty' => $data2['isqty'],
                    'wh' => $data2['whid'],
                ];

                $computecost = $this->computecost($config, $data2, $data[$key]['line'], $logs);
            }
        }
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved successfully', 'data' => $returndata];
    }

    public function save($config)
    {
        $row = $config['params']['row'];
        $trno = $config['params']['tableid'];
        $user = $config['params']['user'];
        $data = [];

        $dateTables = ['lastock'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], 0, [], false, $dateTables);

        foreach ($this->fields as $key2 => $value) {
            // $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
            $data[$value] = $this->othersClass->sanitizekeyfieldFast($value, $row[$value], $lookups);
        }
        $computedata = $this->computepartsprice($config, $row);

        $data['ext'] = $computedata['ext'];
        $data['amt'] = $computedata['amt'];
        $data['iss'] = $computedata['qty'];
        $data['isqty'] = $data['isqty'];
        $taskline = $data['taskline'];
        $jobline = $data['jobline'];

        $qry = "select amjobs.jobid,am.laborline,jt.code, jt.description,job.docno,job.jobtitle
                from amtask as am
                left join amjobs on amjobs.line=am.jobline
                left join jobtask as jt on jt.line=am.laborline
                left join jobthead as job on job.line=amjobs.jobid
                where am.line=$taskline and am.jobline=$jobline";
        $ress = $this->coreFunctions->opentable($qry);

        $item = $this->coreFunctions->opentable("select barcode,itemname from item where itemid =?", [$data['itemid']]);


        if ($row['line'] == 0) { // insert
            $line = $this->coreFunctions->datareader("select line as value from $this->table where trno=? order by line desc limit 1", [$data['trno']]);

            if ($line == '') {
                $line = 0;
            }
            $line = $line + 1;
            $data['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
            $data['encodedby'] = $config['params']['user'];
            $data['line'] = $line;
            if ($line != 0) {
                $this->coreFunctions->insertGetId($this->table, $data);

                // var_dump($data);
                // break;
                // $item = $this->coreFunctions->opentable("select barcode,itemname from item where itemid =?", [$data['itemid']]);
                $this->logger->sbcwritelog2($trno, $user, 'STOCK', 'ADD: PARTS / ITEM - Line: ' . $line . ', Product ID: ' . $item[0]->barcode
                    . ', Product Name: ' . $item[0]->itemname . ', added to Labor "' . $ress[0]->description
                    . '" under Job "' . $ress[0]->jobtitle . '".', 'table_log', 0);

                //add
                $logs = [
                    'barcode' => $item[0]->barcode,
                    'amt' => $data['isamt'],
                    'disc' => $data['disc'],
                    'qty' => $data['isqty'],
                    'wh' => $data['whid'],
                ];

                $computecost = $this->computecost($config, $data, $line, $logs);
                $returnrow = $this->loaddataperrecord($config, $line);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
                // }
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else { // update
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            $update = $this->coreFunctions->sbcupdate($this->table, $data, ['trno' => $row['trno'], 'line' => $row['line']]);
            if ($update) {

                $logs = [
                    'barcode' => $item[0]->barcode,
                    'amt' => $data['isamt'],
                    'disc' => $data['disc'],
                    'qty' => $data['isqty'],
                    'wh' => $data['whid'],
                ];
                $computecost = $this->computecost($config, $data, $row['line'], $logs);
                $returnrow = $this->loaddataperrecord($config, $row['line']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Update failed.'];
            }
        }
    } // end function

    private function loaddataperrecord($config, $line)
    {
        $trno = $config['params']['tableid'];
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " as stock 
        left join item on item.itemid=stock.itemid
         left join client as wh on wh.clientid=stock.whid
         left join location on location.line=stock.locid
        where stock.trno = $trno and  stock.line =?";
        return $this->coreFunctions->opentable($qry, [$line]);
    }

    public function delete($config)
    {
        // var_dump($config['params']);
        // break;
        $table = $config['docmodule']->tablenum;
        $row = $config['params']['row'];
        $trno = $config['params']['tableid'];
        $docno = $this->coreFunctions->getfieldvalue($table, 'docno', 'trno=?', [$trno]);
        $qry = "delete from " . $this->table . " where line=? and trno=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line'], $trno]);
        $item = $this->coreFunctions->opentable("select barcode,itemname from item where itemid =?", [$row['itemid']]);
        $config['params']['docno'] = $docno;
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVE LINE: ' . $row['line'] . ' - Product ID: ' . $item[0]->barcode . ' ' . 'Product name : ' . $item[0]->itemname);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }

    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'addparts':
                return $this->addparts($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
                break;
        }
    }


    public function addparts($config)
    {

        $taskline = $config['params']['sourcerow']['taskline'];
        $jobline = $config['params']['sourcerow']['jobline'];
        $lookupsetup = array(
            'type' => 'multi',
            'rowkey' => 'itemid',
            'title' => 'List of Parts',
            'style' => 'width:800px;max-width:800px;'
        );

        $plotsetup = array(
            'plottype' => 'multientry',
            'action' => 'entryparts',
        );

        // lookup columns
        $cols = [
            ['name' => 'barcode', 'label' => 'Item Code', 'align' => 'left', 'field' => 'barcode', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'itemname', 'label' => 'Item Name', 'align' => 'left', 'field' => 'itemname', 'sortable' => true, 'style' => 'font-size:16px;']
        ];
        $qry = "select itemid,barcode,itemname,uom,amt,disc,$taskline as taskline,$jobline as jobline from item
        where isinactive = 0  and isfa=0 order by itemname";
        $data = $this->coreFunctions->opentable($qry);

        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }

    public function lookupcallback($config)
    {
        $trno = $config['params']['tableid'];
        $row = $config['params']['rows'];
        $returndata = [];
        $status = true;
        $msg = 'Successfully added.';
        foreach ($row  as $key2 => $value) {

            $config['params']['row']['line'] = 0;
            $config['params']['row']['trno'] = $trno;
            $config['params']['row']['taskline'] = $row[$key2]['taskline'];
            $config['params']['row']['jobline'] = $row[$key2]['jobline'];
            $config['params']['row']['itemid'] = $row[$key2]['itemid'];

            $config['params']['row']['uom'] = $row[$key2]['uom'];
            // $config['params']['row']['iss'] = $computedata['qty'];
            $config['params']['row']['isqty'] = 1;
            $config['params']['row']['isamt'] = $row[$key2]['amt'];
            // $config['params']['row']['amt'] = number_format($computedata['amt'] * $forex, $this->companysetup->getdecimal('price', $config['params']), '.', '');
            $config['params']['row']['disc'] = $row[$key2]['disc'];
            $config['params']['row']['rem'] = '';
            $config['params']['row']['ext'] = 0;
            // $config['params']['row']['ext'] = number_format($computedata['ext'], $this->companysetup->getdecimal('currency', $config['params']), '.', '');
            $data[0]['wh'] = $this->companysetup->getwh($config['params']);
            $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$data[0]['wh']]);
            $config['params']['row']['whid'] = $whid;
            $config['params']['row']['bgcolor'] = 'bg-blue-2';
            $return = $this->save($config);
            if ($return['status']) {
                array_push($returndata, $return['row'][0]);
            } else {
                $status = false;
                $msg = $return['msg'];
            }
        }

        return ['status' => $status, 'msg' => $msg, 'data' => $returndata];
    } // end function

    public function computepartsprice($config, $row)
    {
        $kgs = 0;
        // var_dump($row);

        $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor,tqty,lastpr from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
        $item = $this->coreFunctions->opentable($qry, [$row['uom'], $row['itemid']]);
        $isamt = (float) str_replace(',', '', $row['isamt']);
        $factor = 1;
        if (!empty($item)) {
            $item[0]->factor = $this->othersClass->val($item[0]->factor);
            if ($item[0]->factor !== 0) $factor = $item[0]->factor;
        }
        $qty = round($row['isqty'], $this->companysetup->getdecimal('qty', $config['params']));
        if ($this->companysetup->getisdiscperqty($config['params'])) {
            $computedata = $this->othersClass->computestock($isamt, $row['disc'], $qty, $factor, 0, 'P', $kgs, 0, 1);
        } else {
            $computedata = $this->othersClass->computestock($isamt, $row['disc'], $qty, $factor, 0, 'P', $kgs);
        }
        // $computedata['forex'] = $forex;
        $computedata['factor'] = $factor;
        return $computedata;
    }


    public function computecost($config, $data, $line, $logs)
    {
        $barcode = $logs['barcode'];
        $amt = $logs['amt'];
        $disc = $logs['disc'];
        $qty = $logs['qty'];
        $wh = $logs['wh'];

        $loc = '';
        $expiry = '';
        $trno = $config['params']['tableid'];

        $cost = $this->othersClass->computecosting($data['itemid'], $data['whid'], $loc, $expiry, $trno, $line, $data['iss'], $config['params']['doc'], $config['params']['companyid']);

        if ($cost != -1) {
            $this->coreFunctions->sbcupdate('lastock', ['cost' => $cost], ['trno' => $trno, 'line' => $line]);

            if ($this->companysetup->checkbelowcost($config['params'])) {

                $belowcost = $this->othersClass->checkbelowcost($trno, $line, $config);
                if ($belowcost == 1) {
                    return ['status' => false, 'msg' => '(' . $barcode . ') Is this free of charge? Please check.'];
                }

                if ($belowcost == 2) {
                    $this->coreFunctions->sbcupdate('lastock', [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'BELOW COST', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
                    $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
                    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'BELOW COST - Line:' . $line . ' barcode:' . $barcode . ' Qty:' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0', $this->tablelogs);
                    return ['status' => false, 'msg' => '(' . $barcode . ') You cannot issue this item because it is below cost.'];
                }
            }
            return ['status' => true, 'msg' => 'Cost successfully computed.'];
        } else {
            $this->coreFunctions->sbcupdate('lastock', [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
            $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
            $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $line . ' barcode:' . $barcode . ' Qty:' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0', $this->tablelogs);
            return ['status' => false, 'msg' => '(' . $barcode . ') Item is out of stock.'];
        }
    }
} //end class
