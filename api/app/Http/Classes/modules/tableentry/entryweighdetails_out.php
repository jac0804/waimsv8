<?php

namespace App\Http\Classes\modules\tableentry;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;
use App\Http\Classes\sbcscript\sbcscript;

class entryweighdetails_out
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $logger;
    private $warehousinglookup;

    public $modulename = 'Weight details';
    public $gridname = 'inventory';

    public $tablelogs = 'table_log';

    public $style = 'width:500px;max-width:500px;';
    public $issearchshow = true;
    public $showclosebtn = true;

    private $AllowItem = false;

    private $sbcscript;

    public function __construct($params = null)
    {
        $this->fieldClass = new txtfieldClass($params);
        $this->tabClass = new tabClass($params);;
        $this->coreFunctions = new coreFunctions;
        $this->companysetup = new companysetup;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->sbcscript = new sbcscript;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 82
        );
        return $attrib;
    }

    public function createHeadField($config)
    {
        $fields = [];
        $col1 = $this->fieldClass->create($fields);

        return array('col1' => $col1);
    }

    public function paramsdata($config)
    {
        if (isset($config['params']['row'])) {
            $trno = $config['params']['row']['trno'];
            $line = $config['params']['row']['line'];
        } else {
            $trno = $config['params']['dataparams']['trno'];
            $line = $config['params']['dataparams']['line'];
        }

        return $this->getheaddata($trno, $config['params']['doc']);
    }

    public function getheaddata($trno, $doc)
    {
        return [];
    }

    public function data()
    {
        return [];
    }

    public function createTab($config)
    {
        if (isset($config['params']['row'])) {
            $trno = $config['params']['row']['trno'];
            $line = $config['params']['row']['line'];
        } else {
            $trno = $config['params']['dataparams']['trno'];
            $line = $config['params']['dataparams']['line'];
        }

        $this->AllowItem =  $this->coreFunctions->datareader("select item.iscatchwt as value from lastock as s left join item on item.itemid=s.itemid where s.trno=" . $trno . " and s.line=" . $line, [], '', true);

        $action = 0;
        $weight = 1;

        $column = ['action', 'weight'];
        $tab = [$this->gridname => ['gridcolumns' => $column]];

        $stockbuttons = $this->AllowItem ? ['delete'] : [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0]['inventory']['columns'][$weight]['readonly'] = true;

        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $trno = $config['params']['row']['trno'];
        $isposted = $this->othersClass->isposted2($trno, "cntnum");
        if ($isposted) {
            $tbuttons = [];
        } else {
            $tbuttons = $this->AllowItem ? ['whlog'] : [];
        }
        $obj = $this->tabClass->createtabbutton($tbuttons);
        if ($this->AllowItem) {
            $obj[0]['label'] = 'Check available box';
            $obj[0]['lookupclass'] = 'addbox';
        }
        return $obj;
    }

    public function loaddata($config)
    {
        if (isset($config['params']['rows'])) {
            $trno = $config['params']['rows'][0]['trno'];
            $line = $config['params']['rows'][0]['line'];
            $loc = $config['params']['rows'][0]['loc'];
            $expiry = $config['params']['rows'][0]['expiry'];
        } else {
            $trno = $config['params']['row']['trno'];
            $line = $config['params']['row']['line'];
            $loc = $config['params']['row']['loc'];
            $expiry = $config['params']['row']['expiry'];
        }

        $data = $this->coreFunctions->opentable("select trno, line, sline, weight, '" . $loc . "' as loc, '" . $expiry . "' as expiry, '' as bgcolor from serialout where trno=? and line=? order by sline", [$trno, $line]);
        return $data;
    }

    public function delete($config)
    {
        $row = $config['params']['row'];
        $doc = $config['params']['doc'];
        $trno = $config['params']['row']['trno'];
        $line = $config['params']['row']['line'];

        $msg = '';
        $status = true;

        $qry = "update serialin set outline=0 where outline=?";
        $this->coreFunctions->execqry($qry, 'update', [$row['sline']]);
        $qry = "delete from serialout where sline=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['sline']]);

        $path = '';
        switch ($doc) {
            case 'SJ':
                $path = 'App\Http\Classes\modules\sales\\' . strtolower($doc);
                break;
            case 'AJ':
            case 'IS':
            case 'TS':
                $path = 'App\Http\Classes\modules\inventory\\' . strtolower($doc);
                break;
            default:
                $path = 'App\Http\Classes\modules\purchase\\' . strtolower($doc);
                break;
        }

        $config['params']['trno'] = $trno;
        $config['params']['line'] = $line;
        $stockline =  app($path)->openstockline($config);
        if (!empty($stockline)) {
            $totalqty = $this->coreFunctions->datareader("select ifnull(sum(weight),0) as value from serialout where trno=? and line=?", [$trno, $line], '', true);

            $config['params']['data']['trno'] = $trno;
            $config['params']['data']['line'] = $line;
            $config['params']['data']['itemid'] = $stockline[0]->itemid;
            $config['params']['data']['uom'] =  $stockline[0]->uom;
            $config['params']['data']['wh'] =  $stockline[0]->wh;
            $config['params']['data']['isamt'] =  $stockline[0]->isamt;
            $config['params']['data']['expiry'] = $stockline[0]->expiry;
            $config['params']['data']['loc'] = $stockline[0]->loc;
            $config['params']['trno'] = $trno;
            $config['params']['data']['isqty'] = $totalqty;
            $return = app($path)->additem('update', $config);
            if ($return) {
            } else {
                $status = false;
                $msg .= "Failed to update update.<br>";
            }
        }

        if ($msg == '') {
            $msg == 'Successfully deleted.';
        }

        $config['params']['trno'] = $trno;
        $stock = app($path)->openstock($trno, $config);

        return ['status' => $status, 'msg' => $msg, 'reloadgriddata' => ['inventory' => $stock]];
    }

    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'addbox':
                return $this->lookupbox($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup'];
                break;
        }
    }

    public function lookupbox($config)
    {
        $trno = $config['params']['row']['trno'];
        $line = $config['params']['row']['line'];
        $itemid = $config['params']['row']['itemid'];
        $whid = $config['params']['row']['whid'];
        $loc = $config['params']['row']['loc'];
        $expiry = $config['params']['row']['expiry'];

        $addOnTitle = '';
        if ($loc != '') $addOnTitle .= ' | Location: ' . $loc;
        if ($expiry != '') $addOnTitle .= ' | Expiry: ' . $expiry;

        $lookupsetup = array(
            'type' => 'multi',
            'rowkey' => 'sline',
            'title' => 'Available boxes ' . $addOnTitle,
            'style' => 'width:70%;max-width:70%;'
        );

        $plotsetup = array(
            'plottype' => 'callback',
            'action' => 'addboxtogrid',
        );

        $cols = [
            ['name' => 'weight', 'label' => 'Weight per BOX', 'align' => 'left', 'field' => 'weight', 'sortable' => true, 'style' => 'font-size:16px;']
        ];

        $qry = "select rs.loc, rs.expiry, ser.weight, ser.sline, " . $trno . " as trno, " . $line . " as line, " . $itemid . " as itemid
            from rrstatus as rs left join serialin as ser on ser.trno=rs.trno and ser.line=rs.line
            where itemid=" . $itemid . " and whid=" . $whid . " and loc='" . $loc . "' and expiry='" . $expiry . "' and bal<>0 and ser.outline=0";

        $data = $this->coreFunctions->opentable($qry);

        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }
}
