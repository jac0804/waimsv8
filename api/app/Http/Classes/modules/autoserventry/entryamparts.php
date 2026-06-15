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
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $fields = ['trno', 'itemid', 'taskline', 'jobline', 'uom', 'ext', 'rem', 'isamt', 'isqty', 'disc'];
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

        $columns = ['action',  'barcode', 'isqty', 'itemname', 'isamt', 'disc', 'ext', 'rem']; // 'packname'
        $tab = [$this->gridname => ['gridcolumns' => $columns]];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $stockbuttons = ['save', 'delete'];
        $tab = [$this->gridname => ['gridcolumns' => $columns]];
        $obj = $this->tabClass->createTab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$barcode]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$itemname]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$rem]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
        // $obj[0][$this->gridname]['columns'][$packname]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
        // $obj[0][$this->gridname]['columns'][$packname]['label'] = 'Package';

        $obj[0][$this->gridname]['columns'][$barcode]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$barcode]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$barcode]['label'] = 'Product Id';
        $obj[0][$this->gridname]['columns'][$itemname]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$itemname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$itemname]['label'] = 'Product Name';
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['addoutlet', 'saveallentry', 'whlog']; //'whlog'
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[0]['lookupclass'] = 'addparts';
        $obj[0]['action'] = 'lookupsetup';
        return $obj;
    }

    public function loaddata($config)
    {
        $row = isset($config['params']['sourcerow']) ? $config['params']['sourcerow'] : $config['params']['row'];

        $row = $config['params']['row'];
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
        $select = $this->selectqry() . ", '' as bgcolor";
        $qry = "select " . $select . " from " . $this->table . " as stock
        left join item on item.itemid=stock.itemid
        left join amtask as task on task.line = stock.taskline
        left join amjobs as job on job.line = stock.jobline
        left join jobthead as jt on jt.line = job.jobid 
        where stock.trno = ? and stock.jobline = " . $row['jobline'] . " and stock.taskline = " . $row['taskline'] . $filtersearch . " order by stock.line";
        $data = $this->coreFunctions->opentable($qry, [$trno]);
        return $data;
    }

    private function selectqry()
    {
        $query = "stock.line,stock.jobline,stock.taskline,stock.amt,item.barcode,item.itemname,item.uom,format(stock.isqty,2) as isqty,stock.iss,stock.disc,stock.itemid,stock.uom,stock.trno,stock.line,stock.rem,format(stock.ext,2) as ext,format(stock.isamt,2) as isamt";
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
        $doc = $config['params']['doc'];
        $tableid = $config['params']['tableid'];
        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }
                $computedata = $this->computepartsprice($config, $data[$key]);

                $data['ext'] = $computedata['ext'];
                $data['amt'] = $computedata['amt'];
                $data['iss'] = $computedata['qty'];
                $data['isqty'] = $data[$key]['isqty'];

                if ($data[$key]['line'] == 0) {
                    $data['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
                    $data['encodedby'] = $config['params']['user'];

                    $line = $this->coreFunctions->insertGetId($this->table, $data2);
                    $config['params']['doc'] = 'ENTRYPARTS';

                    $item = $this->coreFunctions->opentable("select barcode,itemname from item where item =?", [$data2['itemid']]);
                    $this->logger->sbcmasterlog($line, $config, ' CREATE - Line: ' . $line . 'Job Code :' . $item[0]->barcode . ' ' . 'Job Desc : ' . $item[0]->itemname);
                } else {
                    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['editby'] = $config['params']['user'];
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
                }
            }
        }
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved successfully', 'data' => $returndata];
    }

    public function save($config)
    {
        $row = $config['params']['row'];
        $trno = $config['params']['tableid'];
        $data = [];
        foreach ($this->fields as $key2 => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }
        $computedata = $this->computepartsprice($config, $row);

        $data['ext'] = $computedata['ext'];
        $data['amt'] = $computedata['amt'];
        $data['iss'] = $computedata['qty'];
        $data['isqty'] = $data['isqty'];

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
                $config['params']['doc'] = 'ENTRYPARTS';
                $item = $this->coreFunctions->opentable("select barcode,itemname from item where itemid =?", [$data['itemid']]);
                $this->logger->sbcmasterlog($trno, $config, ' CREATE - Line: ' . $line . ' Product ID : ' . $item[0]->barcode . ' ' . 'Product Name : ' . $item[0]->itemname, 0, 0, $row['taskline']);
                $returnrow = $this->loaddataperrecord($config, $line);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else { // update
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            $update = $this->coreFunctions->sbcupdate($this->table, $data, ['trno' => $row['trno'], 'line' => $row['line']]);
            if ($update) {
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
        left join pttask as task on task.line = stock.taskline
        left join ptjobs as job on job.line = stock.jobline
        left join jobthead as jt on jt.line = job.jobid 
        where stock.trno = $trno and  stock.line =?";
        return $this->coreFunctions->opentable($qry, [$line]);
    }

    public function delete($config)
    {
        $row = $config['params']['row'];
        $trno = $config['params']['tableid'];
        $doc = $config['params']['doc'];
        $qry = "delete from " . $this->table . " where line=? and trno=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line'], $trno]);
        $config['params']['doc'] = 'ENTRYPARTS';
        $item = $this->coreFunctions->opentable("select barcode,itemname from item where itemid =?", [$row['itemid']]);
        $this->logger->sbcdelmaster_log($trno, $config, 'REMOVE LINE: ' . $row['line'] . ' - Job Code: ' . $item[0]->barcode . ' ' . 'Job Desc : ' . $item[0]->itemname, 0, $row['taskline']);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }

    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'whlog':
                return $this->lookuplogs($config);
                break;
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

    public function lookuplogs($config)
    {
        $taskline = $config['params']['sourcerow']['taskline'];
        $doc = 'ENTRYPARTS';
        $lookupsetup = array(
            'type' => 'show',
            'title' => 'Logs',
            'style' => 'width:1000px;max-width:1000px;'
        );

        // lookup columns
        $cols = array(
            array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')
        );

        $trno = $config['params']['tableid'];

        $qry = "
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from " . $this->tablelogs . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and log.trno = $trno and log.trno2 = $taskline
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and log.trno = $trno and log.trno2 = $taskline";

        $qry = $qry . " order by dateid desc";
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
    }

    public function computepartsprice($config, $row)
    {
        $kgs = 0;
        // var_dump($row);

        $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor,tqty,lastpr from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
        $item = $this->coreFunctions->opentable($qry, [$row['uom'], $row['itemid']]);

        $factor = 1;
        if (!empty($item)) {
            $item[0]->factor = $this->othersClass->val($item[0]->factor);
            if ($item[0]->factor !== 0) $factor = $item[0]->factor;
        }
        $qty = round($row['isqty'], $this->companysetup->getdecimal('qty', $config['params']));
        if ($this->companysetup->getisdiscperqty($config['params'])) {
            $computedata = $this->othersClass->computestock($row['isamt'], $row['disc'], $qty, $factor, 0, 'P', $kgs, 0, 1);
        } else {
            $computedata = $this->othersClass->computestock($row['isamt'], $row['disc'], $qty, $factor, 0, 'P', $kgs);
        }
        // $computedata['forex'] = $forex;
        $computedata['factor'] = $factor;
        return $computedata;
    }
} //end class
