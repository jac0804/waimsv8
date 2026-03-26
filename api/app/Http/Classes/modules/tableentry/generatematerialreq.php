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

class generatematerialreq
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'MATERIAL REQUEST';
    public $gridname = 'inventory';
    private $head = 'hmrhead';
    private $stock = 'hmrstock';
    public $tablenum = 'transnum';
    private $companysetup;
    private $coreFunctions;
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';
    private $othersClass;
    public $style = 'width:50%;max-width:50%;';
    public $showclosebtn = false;
    public $fields = ['prqty'];
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
            'load' => 0
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $cols = ['itemname', 'qa', 'prqty', 'uom'];
        foreach ($cols as $key => $value) {
            $$value = $key;
        }
        $tab = [
            $this->gridname => [
                'gridcolumns' => $cols
            ]
        ];

        $stockbuttons = [];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$uom]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$uom]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$itemname]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$itemname]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$itemname]['label'] = 'Itemname';
        $obj[0][$this->gridname]['columns'][$qa]['type'] = 'label';
        return $obj;
    }


    public function createtabbutton($config)
    {

        $config['params']['trno'] = $config['params']['tableid'];
        $tbuttons = [];
        $isposted = $this->othersClass->isposted($config);
        if ($isposted) {
            array_push($tbuttons, 'saveall');
        }
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[0]['action'] = 'generatepr';
        $obj[0]['label'] = 'GENERATE PR';
        return $obj;
    }
    public function getdata($config)
    {
        $trno = $config['params']['tableid'];
        $msg = '';
        $status = true;
        $data = $config['params']['data'];
        foreach ($data as $key => $value) {
            if ($value['bgcolor'] != '') {
                $qty = $value['prqty'];
                $var = [
                    'prqty' => $qty
                ];
                $this->coreFunctions->sbcupdate($this->stock, $var, ['trno' => $value['trno'], 'line' => $value['line']]);
                $msg = 'Saved changes...';
            }
        }
        $data = $this->coreFunctions->opentable("select s.trno,s.line,s.itemid,i.itemname,
        round((s.iss-(s.qa+s.prqa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end,2) as qa,s.prqty,
        s.uom,'' as bgcolor from hmrstock as s left join item as i on i.itemid=s.itemid
        left join uom on uom.itemid = i.itemid and uom.uom = s.uom 
        where s.trno = ? and s.iss>(s.prqa+s.qa)", [$trno]);

        $config['params']['trno'] =  $trno;
        if (!empty($data)) {
            $return =  $this->othersClass->generateShortcutTransaction($config);
            if ($return['status']) {
                $this->coreFunctions->execqry("update hmrstock set prqty = 0 where trno =?", "update", [$trno]);
            }
            return $return;
        } else {
            return ['status' => false, 'msg' => 'No data found'];
        }

        return ['status' => $status, 'msg' => $msg, 'data' => $data];
    } //end function

    public function tableentrystatus($config)
    {
        $trno = $config['params']['tableid'];
        $data = $this->loaddata($config);
        $config['params']['trno'] =  $trno;
        if (!empty($data)) {
            $return =  $this->othersClass->generateShortcutTransaction($config);
            if ($return['status']) {
                $this->coreFunctions->execqry("update hmrstock set prqty = 0 where trno =?", "update", [$trno]);
            }
            return $return;
        } else {
            return ['status' => false, 'msg' => 'No data found'];
        }
    }
    public function loaddata($config)
    {
        $trno = $config['params']['tableid'];
        $data = $this->coreFunctions->opentable("select s.trno,s.line,s.itemid,i.itemname,
        round((s.iss-(s.qa+s.prqa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end,2) as qa,
        s.prqty, s.uom,'' as bgcolor from hmrstock as s left join item as i on i.itemid=s.itemid
        left join uom on uom.itemid = i.itemid and uom.uom = s.uom 
        where s.trno = ? and s.iss>(s.prqa+s.qa)", [$trno]);
        return $data;
    }
} //end class
