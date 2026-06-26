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
use App\Http\Classes\tableentryClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class entryreplacementcheque
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Replacement Cheque';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'chequedetail';
    private $htable = 'hchequedetail';
    public $tablelogs = 'table_log';
    public $htablelogs = 'htable_log';
    private $othersClass;
    public $style = 'width:100%;max-width:1300px;';
    private $fields = ['trno', 'line', 'refx', 'linex', 'rctrno', 'rcline'];
    public $showclosebtn = true;
    private $reporter;
    private $logger;
    private $sqlquery;


    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->reporter = new SBCPDF;
        $this->logger = new Logger;
        $this->sqlquery = new sqlquery;
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
        $trno = $config['params']['row']['trno'];
        $isposted = $this->othersClass->isposted2($trno, "cntnum");

        $columns = ['action', 'docno', 'bank', 'amount', 'checkdate', 'checkno'];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $columns]];

        $stockbuttons = [];

        if (!$isposted) {
            array_push($stockbuttons, 'delete');
        }

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$docno]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][$bank]['style'] = "width:140pxwhiteSpace: normal;min-width:140px";
        $obj[0][$this->gridname]['columns'][$amount]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
        $obj[0][$this->gridname]['columns'][$checkno]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";

        $obj[0][$this->gridname]['columns'][$docno]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$bank]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$amount]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$checkdate]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$checkno]['readonly'] = true;
        return $obj;
    }


    public function createtabbutton($config)
    {
        $trno = $config['params']['row']['trno'];
        $isposted = $this->othersClass->isposted2($trno, "cntnum");

        $tbuttons = [];
        if (!$isposted) {
            array_push($tbuttons, 'additem');
        }
        foreach ($tbuttons as $key => $value) {
            $$value = $key;
        }
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[$additem]['lookupclass'] = 'addbouncedcheque';
        $obj[$additem]['action'] = 'lookupsetup';
        $obj[0]['label'] = 'ADD REPLACEMENT CHEQUE';

        return $obj;
    }

    private function selectqry()
    {
        $qry = "head.docno,d.checkno,format(d.amount,2) as amount,d.bank,d.branch,date(d.checkdate) as checkdate,
        detail.trno,detail.line,detail.refx,detail.linex,detail.rctrno,detail.rcline";
        return $qry;
    }

    public function loaddata($config)
    {
        $row = $config['params']['row'];
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from chequedetail as detail
				left join hrcdetail as d on  d.trno = detail.rctrno and d.line = detail.rcline
				left join hrchead as head on head.trno = d.trno
				where detail.trno = " . $row['trno'] . " and detail.line = " . $row['line'] . "";
        return $this->coreFunctions->opentable($qry);
    }


    public function loaddataperrecord($trno, $line, $rctrno, $rcline)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from chequedetail as detail
				left join hrcdetail as d on  d.trno = detail.rctrno and d.line = detail.rcline
				left join hrchead as head on head.trno = d.trno
				where detail.trno = " . $trno . " and detail.line = " . $line . " and  detail.rctrno = " . $rctrno . " and detail.rcline = " . $rcline . "";
        return $this->coreFunctions->opentable($qry);
    }

    public function save($config)
    {
        $row = $config['params']['row'];
        $data = [];
        foreach ($this->fields as $key2 => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }


        $data['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
        $data['encodedby'] = $config['params']['user'];
        if ($this->coreFunctions->sbcinsert($this->table, $data)) {

            if ($data['refx'] != 0) {
                $this->coreFunctions->execqry("update hparticulars set retrno = " . $data['trno'] . " where trno =? and line =? ", "update", [$data['refx'], $data['linex']]);
            }
            $this->coreFunctions->execqry("update hrcdetail set retrno = " . $data['trno'] . " where trno =? and line =? ", "update", [$data['rctrno'], $data['rcline']]);

            $checkno = $this->coreFunctions->datareader("select checkno as value from hrcdetail where trno = " . $data['rctrno'] . " and line  = " . $data['rcline'] . "");
            $this->logger->sbcwritelog($row['trno'], $config, 'DETAIL', 'ADD - Line: ' . $data['rcline'] . ' Check #: ' . $checkno);
            $returnrow = $this->loaddataperrecord($data['trno'], $data['line'], $data['rctrno'], $data['rcline']);
            return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
        } else {
            return ['status' => false, 'msg' => 'Failed to save bounce cheque. Please advice the admin', 'row' => []];
        }
    }
    public function delete($config)
    {
        $row = $config['params']['row'];
        $qry = "delete from " . $this->table . " where trno = " . $row['trno'] . " and line= " . $row['line'] . " and rctrno = " . $row['rctrno'] . " and rcline = " . $row['rcline'] . "";
        $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line'], $row['rctrno'], $row['rcline']]);

        if ($row['rctrno'] != 0) {
            $this->coreFunctions->sbcupdate('hrcdetail', ['retrno' => 0], ['trno' => $row['rctrno'], 'line' => $row['rcline']]);
        }
        $this->logger->sbcwritelog($row['trno'], $config, 'DETAIL',  'REMOVE - Checkno: ' . $row['checkno']);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }
    public function lookupsetup($config)
    {

        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'addbouncedcheque':
                return $this->addbouncedcheque($config);
                break;

            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
                break;
        }
    }
    public function addbouncedcheque($config)
    {
        $row = $config['params']['row'];
        $lookupsetup = array(
            'type' => 'multi',
            'rowkey' => 'rc',
            'title' => 'List of Replacement Checks',
            'style' => 'width:800px;max-width:800px;'
        );
        $plotsetup = array(
            'plottype' => 'callback',
            'action' => 'replacement'
        );

        // lookup columns
        $cols = [
            ['name' => 'docno', 'label' => 'Document#', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'bank', 'label' => 'Bank', 'align' => 'left', 'field' => 'bank', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'branch', 'label' => 'Branch', 'align' => 'left', 'field' => 'branch', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'checkdate', 'label' => 'Check Date', 'align' => 'left', 'field' => 'checkdate', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'checkno', 'label' => 'Check No.', 'align' => 'left', 'field' => 'checkno', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'amount', 'label' => 'Amount', 'align' => 'left', 'field' => 'amount', 'sortable' => true, 'style' => 'font-size:16px;']

        ];
        $qry = "select d.trno as rctrno,d.line as rcline,concat(d.trno,'~',d.line) as rc ,h.docno,d.checkno,d.amount,d.bank,d.branch,date(d.checkdate) as checkdate,
         " . $row['trno'] . " as trno," . $row['line'] . " as line," . $row['refx'] . " as refx," . $row['linex'] . " as linex
            from hrcdetail as d
            left join hrchead as h on h.trno=d.trno
            where ortrno = 0 and retrno=0";
        $data = $this->coreFunctions->opentable($qry);

        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }
} //end class
