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

class entrysoposted
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'STOCK SO POSTED';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $tablestock = 'omstock';
    private $htablestock = 'homstock';
    private $htable = 'homso';
    public $tablelogs = 'transnum_log';
    private $logger;
    private $othersClass;
    public $style = 'width:100%';
    private $fields = ['trno', 'line', 'soline',  'sono', 'rtno', 'rem', 'qty'];
    public $showclosebtn = false;
    public $showsearch = false;



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
        $trno = $config['params']['tableid'];
        $isposted = $this->othersClass->isposted2($trno, "transnum");

        $action = 0;
        $sono = 1;
        $rtno = 2;
        $rem = 3;
        $qty = 4;

        $gridcolumns = ['action', 'sono', 'rtno', 'rem', 'qty'];
        $stockbuttons = ['delete'];
        $tab = [$this->gridname => ['gridcolumns' => $gridcolumns]];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
        $obj[0][$this->gridname]['columns'][$sono]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$qty]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$rtno]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$rem]['style'] = "width:170px;whiteSpace: normal;min-width:170px;";

        $obj[0][$this->gridname]['columns'][$qty]['label'] = "Quantity";
        $obj[0][$this->gridname]['columns'][$qty]['type'] = "label";


        return $obj;
    }



    public function createtabbutton($config)
    {
        $trno = $config['params']['tableid'];
        $isposted = $this->othersClass->isposted2($trno, "transnum");
        $tbuttons = ['addrecord', 'saveallentry'];

        if (!$isposted) {
            $tbuttons = [];
        } else {
        }
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[1]['label'] = 'SAVE';
        return $obj;
    }
    public function loaddata($config)
    {
        $trno  = 0;
        $line = 0;
        if (isset($config['params']['ledgerdata'])) {
            $trno = $config['params']['ledgerdata']['trno'];
            $line = $config['params']['ledgerdata']['line'];
        } else {
            $data = $config['params']['data'];
            foreach ($data as $key => $value) {
                $trno = $data[$key]['trno'];
                $line = $data[$key]['line'];
                break;
            }
        }

        $qry = "
            select so.sono, so.rtno,so.qty,stock.trno,stock.line,stock.rrqty ,so.soline,so.rem, '' as bgcolor
           from $this->htablestock  as stock
        left join homso as so on so.trno=stock.trno and so.line=stock.line
              where stock.trno = ? and stock.line=?";
        $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
        return $data;
    }


    public function add($config)
    {
        $data =  $config['params']['data'];
        $trno = $config['params']['data'][0]['trno'];
        $line = $config['params']['data'][0]['line'];
        $soqty = $this->coreFunctions->datareader("select ifnull(sum(qty),0) as value from homso where trno=? and line=? and rtno = ''", [$trno, $line], '', true);
        $bal = $config['params']['data'][0]['rrqty'] -  $soqty;
        if ($bal < 0) {
            $bal = 0;
        }
        $data = [];
        $data['trno'] = $trno;
        $data['line'] = $line;
        $data['soline'] = 0;
        $data['sono'] = '';
        $data['rtno'] = '';
        $data['rem'] = '';
        $data['qty'] = number_format($bal, 2);
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        $msg = '';
        foreach ($data as $key => $value) {
            $trno = $data[$key]['trno'];
            $line = $data[$key]['line'];
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }
                //////////////
                $omqty = 0;

                if ($data[$key]['qty'] == 0) {
                    continue;
                } else {
                    $omqty = $this->coreFunctions->datareader("select rrqty as value from homstock where trno=? and line=?", [$trno, $line], '', true);
                    $soqty = $this->coreFunctions->datareader("select ifnull(sum(qty),0) as value from homso where trno=? and line=? and rtno = '' ", [$trno, $line], '', true);

                    if ($soqty  > $omqty) {
                        $msg .= 'Unable to add SO#, qty exceed.';
                        continue;
                    }
                }
                ///////////////
                if ($data[$key]['soline'] == 0) {
                    $qry = "select soline as value from homso where trno=? and line=? order by soline desc limit 1";
                    $line = $this->coreFunctions->datareader($qry, [$trno, $line]);
                    if ($line == '') {
                        $line = 0;
                    }
                    $line = $line + 1;
                    $data2['soline'] = $line;
                    $this->coreFunctions->sbcinsert($this->htable, $data2);
                    $this->logger->sbcwritelog($trno, $config, 'ADD STOCK', "SO # => " . $data[$key]['sono']);
                } else {
                    if ($data[$key]['rtno'] != '') {
                        if ($data[$key]['rem'] == '') {
                            $msg .= 'Need to input remarks.';
                            continue;
                        }
                    }

                    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['editby'] = $config['params']['user'];
                    $updatefields =   ['editby' => $data2['editby'], 'editdate' =>  $data2['editdate'], 'sono' => $data2['sono'], 'rtno' => $data2['rtno'], 'rem' => $data2['rem'], 'trno' => $data2['trno']];
                    $this->coreFunctions->sbcupdate($this->htable, $updatefields, ['trno' => $data[$key]['trno'], 'line' => $data[$key]['line'], 'soline' => $data[$key]['soline']]);
                    $this->logger->sbcwritelog($trno, $config, "UPDATE STOCK", "SO # => " . $data[$key]['sono']);
                }
            } // end if
        } // foreach

        $returndata = $this->loaddata($config);

        if ($msg == '') {
            $msg == 'All saved successfully.';
        }

        return ['status' => true, 'msg' => $msg, 'data' => $returndata];
    }


    public function delete($config)
    {
        $data = $config['params']['row'];
        if ($data['rtno'] != '') {
            return ['status' => false, 'msg' => 'Can`t delete. Quantity already has RT#.'];
        }
        $qry = "delete from homso where trno=? and line=? and soline=?";
        $this->coreFunctions->execqry($qry, 'delete', [$data['trno'], $data['line'], $data['soline']]);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }
} //end class
