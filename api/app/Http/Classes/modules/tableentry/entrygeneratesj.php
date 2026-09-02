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

class entrygeneratesj
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'GENERATED SJ';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'hsostock';
    private $othersClass;
    public $style = 'width:100%;';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $fields = ['task', 'title', 'userid', 'startdate', 'enddate', 'percentage', 'taskcatid', 'isassigntype'];
    public $showclosebtn = false;
    private $reporter;
    public $logger;
    private $reportheader;


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
        $columns = ['dateid', 'docno', 'client', 'clientname'];
        $tab = [
            $this->gridname => [
                'gridcolumns' => $columns
            ]
        ];
        foreach ($columns as $key => $value) {
            $$value = $key;
        }
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][$client]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $obj[0][$this->gridname]['columns'][$client]['label'] = 'Customer';
        $obj[0][$this->gridname]['columns'][$client]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Customer Name';
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:180px;whiteSpace: normal;min-width:180px;';

        $obj[0][$this->gridname]['columns'][$docno]['label'] = 'Document No.';
        $obj[0][$this->gridname]['columns'][$docno]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$docno]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';

        $obj[0][$this->gridname]['columns'][$dateid]['label'] = 'Transaction Date';
        $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dateid]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';
        return $obj;
    }


    public function createtabbutton($config)
    {
        $tableid = $config['params']['tableid'];
        $tbuttons = [];
        foreach ($tbuttons as $key => $value) {
            $$value = $key;
        }
        $isclose = $this->isclose($config);
        $obj = $this->tabClass->createtabbutton($tbuttons);
        if ($isclose || $tableid == 0) {
            $obj[0]['visible'] = false;
            $obj[1]['visible'] = false;
        }

        return $obj;
    }


    public function add($config)
    {
        $trno = $config['params']['tableid'];

        $data = [];
        return $data;
    }
    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        $companyid = $config['params']['companyid'];
        $trno = $config['params']['tableid'];
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
    } // end function

    // public function save($config)
    // {
    //     $row = $config['params']['row'];
    //     $trno = $config['params']['tableid'];
    //     $qry = "update hsostock set tmtrno=? where trno=? and line=?";
    //     $this->coreFunctions->execqry($qry, 'update', [$trno, $row['sotrno'], $row['soline']]);
    //     $config['params']['doc'] = 'ENNTRYPENDINGSO';
    //     $this->logger->sbcmasterlog($trno, $config, 'ADD - Line : ' . $row['soline'] . ' Item Name: ' . $row['itemname'] . ' Docno : ' . $row['sodocno']);
    //     $returnrow = $this->loaddataperrecord($trno, $row['sotrno'], $row['soline']);
    //     return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
    // } //end function
    public function delete($config)
    {
        $row = $config['params']['row'];
        $trno = $config['params']['tableid'];
        $qry = "update hsostock set tmtrno = 0 where trno=?  and line=? and tmtrno=?";
        $this->coreFunctions->execqry($qry, 'update', [$row['sotrno'], $row['soline'], $trno]);
        $config['params']['doc'] = 'ENNTRYPENDINGSO';
        $this->logger->sbcdelmaster_log($trno, $config, 'REMOVE - Line : Task : ' . $row['itemname'] . ' Docno : ' . $row['docno']);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }

    private function selectqry()
    {
        $qry = "stock.tmtrno,lstock.ref,head.docno,cl.client,head.clientname,date(head.dateid) as dateid";

        return $qry;
    }

    public function loaddata($config)
    {
        $select = $this->selectqry();
        $trno = $config['params']['tableid'];
        $limit = '';
        $filtersearch = "";
        $searcfield = ['docno', 'clientname'];
        $search = '';

        if (isset($config['params']['filter'])) {
            $search = $config['params']['filter'];
            foreach ($searcfield as $key => $sfield) {
                if ($filtersearch == "") {
                    $filtersearch .= " and (head." . $sfield . " like '%" . $search . "%'";
                } else {
                    $filtersearch .= " or head." . $sfield . " like '%" . $search . "%'";
                } //end if
            }
            $filtersearch .= ")";
        }

        if ($search != "") {
            $l = '';
        } else {
            $l = $limit;
        }
        $qry = "
         select " . $select . ",'' as bgcolor from " . $this->table . " as stock
         left join glstock as lstock on lstock.refx = stock.trno and lstock.linex = stock.line
         left join glhead as head on head.trno =lstock.trno
         left join client as cl on cl.clientid = head.clientid
         where stock.tmtrno = ? " . $filtersearch . "
         group by lstock.ref,stock.tmtrno,head.docno,cl.client,head.clientname,head.dateid";
        return $this->coreFunctions->opentable($qry, [$trno]);
    }

    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
                break;
        }
    }
    public function isclose($config)
    {
        $tableid = $config['params']['tableid'];
        $status = $this->coreFunctions->datareader("select status as value from tmhead where trno= ?", [$tableid]);
        if ($status != 2) {
            return false;
        } else {
            return true;
        }
    }
} //end loantype
