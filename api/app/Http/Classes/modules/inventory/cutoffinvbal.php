<?php

namespace App\Http\Classes\modules\inventory;

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
use App\Http\Classes\SBCPDF;

use Exception;
use PhpParser\Node\Expr\FuncCall;

class cutoffinvbal
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Cut Off Inventory';
    public $gridname = 'entrygrid';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $sqlquery;
    private $logger;
    public $tablelogs = 'masterfile_log';
    public $style = 'width:100%;max-width:100%;';
    public $issearchshow = true;
    public $showclosebtn = false;
    public $invbal = 'invbal';
    public $reporter;

    public function __construct()
    {
        $this->btnClass = new buttonClass;
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->sqlquery = new sqlquery;
        $this->logger = new Logger;
        $this->reporter = new SBCPDF;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 4910,
            'view' => 4922,
            'save' => 4923,

        );
        return $attrib;
    }

    public function createHeadbutton($config)
    {
        return [];
    }
    public function createHeadField($config)
    {
        $fields = ['end', ['refresh', 'reset']];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'end.label', 'Cut off day');
        data_set($col1, 'refresh.label', 'Save');
        data_set($col1, 'refresh.action', 'save');
        data_set($col1, 'refresh.style', 'width:100px;whiteSpace: normal;min-width:100px;');
        data_set($col1, 'reset.style', 'width:100px;whiteSpace: normal;min-width:100px;');

        return array('col1' => $col1);
        // return [];
    }
    public function data($config)
    {
        return $this->paramsdata($config);
    }
    public function createTab($config)
    {
        $tab = [];
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
        $cutoff = "curdate()";

        $cutoffexists = $this->coreFunctions->getfieldvalue('profile', 'pvalue', "doc='RX' and psection='INVCUTOFF'");
        if ($cutoffexists != '') {
            $cutoff = "'" . $cutoffexists . "'";
        }

        if (isset($config['params']['dataparams'])) {
            $cutoff = $config['params']['dataparams'];
        }
        $qry = "select " . $cutoff . " as `end`";

        $data = $this->coreFunctions->opentable($qry);
        return $data[0];
    }
    public function headtablestatus($config)
    {
        $action = $config['params']["action2"];
        switch ($action) {
            case 'save':
                return $this->loaddata($config);
                break;
            case 'reset':
                return $this->resetdata($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Please check headtablestatus (' . $action . ')'];
                break;
        }
    }
    private function loaddata($config)
    {
        //ifnull(sum(round(costin-costout,2))/sum(qty-iss),0) as cost

        ini_set('max_execution_time', -1);
        ini_set('memory_limit', '-1');
        $cutoffdate = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
        $query = "select ib.itemid, ib.whid,sum(qty - iss) as bal, loc , ifnull(expiry,''), '$cutoffdate' as dateid, sum(round(costin-costout,2)) as cost
      from (
      select item.itemid,wh.clientid as whid, stock.qty,  stock.iss, stock.loc, stock.expiry, 0 as costin, 0 as costout
      from ((lahead as head
      left join lastock as stock on stock.trno = head.trno)
      left join item on item.itemid = stock.itemid
      left join client as wh on wh.clientid = stock.whid)
      where head.dateid <= '$cutoffdate' and item.isofficesupplies = 0
      union all
      select  item.itemid,wh.clientid as whid,stock.qty,  stock.iss, stock.loc, stock.expiry, 
      case when stock.qty > 0 then (stock.cost*stock.qty) else 0 end as costin,
      case when stock.iss > 0 then (stock.cost*stock.iss) else 0 end as costout
      from ((glhead as head
      left join glstock as stock on stock.trno = head.trno)
      left join item on item.itemid = stock.itemid
      left join client as wh on wh.clientid = stock.whid)
      where head.dateid <= '$cutoffdate'  and item.isofficesupplies = 0
      ) as ib
      group by ib.itemid,ib.whid, loc , expiry
      having (case when sum(qty - iss) > 0 then 1 else 0 end) in (0,1)  order by itemid";
        $data = $this->coreFunctions->opentable($query);
        if (!empty($data)) {
            $delete = $this->coreFunctions->execqry('delete from invbal ', 'delete');
            if ($delete == 1) {
                $data2 = array_map(function ($value) {

                    return [
                        'itemid' => $value->itemid,
                        'whid'   => $value->whid,
                        'bal'    => $value->bal,
                        'loc'    => $value->loc,
                        'dateid' => $value->dateid,
                        'cost' => $value->cost
                    ];
                }, $data);

                $result = $this->coreFunctions->sbcinsert($this->invbal, $data2);
                if ($result == 1) {
                    $this->logger->sbcmasterlog(0, $config, "AUTO GENERATE - INVCUTOFF");
                    $this->adprofile($cutoffdate);
                    return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
                }
            } else {
                return ['status' => false, 'msg' => 'The invbal table dooes not exist. Check fields first.', 'action' => 'load'];
            }
        } else {
            return ['status' => false, 'msg' => 'There was no data found within the cutoff date.', 'action' => 'load'];
        }
    }
    public function resetdata($config)
    {
        $getcutoffline = $this->coreFunctions->getfieldvalue("profile", "line", "doc='RX' and psection='INVCUTOFF'");
        $datacount = $this->coreFunctions->datareader('select count(itemid) as value from invbal');
        $msg = '';
        if (!empty($datacount)) {
            $this->coreFunctions->execqry('delete from invbal ', 'delete');
            $this->coreFunctions->execqry('delete from profile where line =? and psection =?', 'delete', [$getcutoffline, 'INVCUTOFF']);
        } else {
            $msg = 'No data was found to reset.';
        }
        if (empty($msg)) {
            $msg = 'Accounting cut off was successfully reset.';
            $this->logger->sbcmasterlog(0, $config, "RESET - INVCUTOFF");
        }
        
        return ['status' => true, 'msg' => $msg, 'action' => 'load'];
    }
    public function adprofile($cutoffdate)
    {
        $getcutoffline = $this->coreFunctions->getfieldvalue("profile", "line", "doc='RX' and psection='INVCUTOFF'");
        if ($getcutoffline == 0) {
            $data = ['doc' => 'RX', 'psection' => 'INVCUTOFF', 'pvalue' => $cutoffdate];
            $this->coreFunctions->sbcinsert("profile", $data);
        } else {
            $this->coreFunctions->sbcupdate("profile", ['pvalue' => $cutoffdate], ['line' => $getcutoffline, 'psection' => 'INVCUTOFF']);
        }
    }
}
